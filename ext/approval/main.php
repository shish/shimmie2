<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<ApprovalTheme> */
final class Approval extends Extension
{
    public const KEY = "approval";

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["approved"] = ImagePropType::BOOL;
        Image::$prop_types["approved_by_id"] = ImagePropType::INT;
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        if (defined("UNITTEST") || Ctx::$user->can(ApprovalPermission::BYPASS_IMAGE_APPROVAL)) {
            self::approve_image($event->image->id);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("approve_image/{image_id}", method: "POST", permission: ApprovalPermission::APPROVE_IMAGE)) {
            $image_id = int_escape($event->get_arg('image_id'));
            self::approve_image($image_id);
            Ctx::$page->set_redirect(make_link("post/view/" . $image_id));
        }

        if ($event->page_matches("disapprove_image/{image_id}", method: "POST", permission: ApprovalPermission::APPROVE_IMAGE)) {
            $image_id = int_escape($event->get_arg('image_id'));
            self::disapprove_image($image_id);
            Ctx::$page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_form();
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database;

        $action = $event->action;
        $event->redirect = true;
        if ($action === "approval") {
            $approval_action = $event->params["approval_action"];
            switch ($approval_action) {
                case "approve_all":
                    $database->set_timeout(null); // These updates can take a little bit
                    $database->execute(
                        "UPDATE images SET approved = TRUE, approved_by_id = :approved_by_id WHERE approved = FALSE",
                        ["approved_by_id" => Ctx::$user->id]
                    );
                    break;
                case "disapprove_all":
                    $database->set_timeout(null); // These updates can take a little bit
                    $database->execute(
                        "UPDATE images SET approved = FALSE, approved_by_id = NULL WHERE approved = TRUE"
                    );
                    break;
                default:
                    break;
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if (!$this->check_permissions($event->image)) {
            Ctx::$page->set_redirect(make_link());
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            if (!Ctx::$user->is_anonymous()) {
                $event->add_nav_link(search_link(['approved=no']), "Pending Approval", "pending_approval", order: 60);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (!Ctx::$user->is_anonymous()) {
            $event->add_link("Pending Approval", search_link(["approved=no"]), 60);
        }
    }

    public const SEARCH_REGEXP = "/^approved[=:](yes|no)/i";
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term) && $this->no_approval_query($event->context)) {
            $event->add_querylet(new Querylet("approved = TRUE"));
        }

        if ($matches = $event->matches(self::SEARCH_REGEXP)) {
            if (strtolower($matches[1]) === "no") {
                // Admins can see all unapproved posts
                if (Ctx::$user->can(ApprovalPermission::APPROVE_IMAGE)) {
                    $event->add_querylet(new Querylet("approved != TRUE"));
                }
                // Regular users can see their own unapproved posts
                elseif (!Ctx::$user->is_anonymous()) {
                    $event->add_querylet(new Querylet(
                        "approved != TRUE AND owner_id = :approval_owner_id",
                        ["approval_owner_id" => Ctx::$user->id]
                    ));
                } else {
                    // Anonymous users can't see unapproved posts
                    $event->add_querylet(new Querylet("1=0"));
                }
            } else {
                $event->add_querylet(new Querylet("approved = TRUE"));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            if (!Ctx::$user->is_anonymous()) {
                $event->add_section("Approval", $this->theme->get_help_html());
            }
        }
    }

    /**
     * @param string[] $context
     */
    private function no_approval_query(array $context): bool
    {
        foreach ($context as $term) {
            if (\Safe\preg_match(self::SEARCH_REGEXP, $term)) {
                return false;
            }
        }
        return true;
    }

    public static function approve_image(int $image_id): void
    {
        global $database;

        $database->execute(
            "UPDATE images SET approved = TRUE, approved_by_id = :approved_by_id WHERE id = :id AND approved = FALSE",
            ["approved_by_id" => Ctx::$user->id, "id" => $image_id]
        );
    }

    public static function disapprove_image(int $image_id): void
    {
        global $database;

        $database->execute(
            "UPDATE images SET approved = FALSE, approved_by_id = NULL WHERE id = :id AND approved = TRUE",
            ["id" => $image_id]
        );
    }

    private function check_permissions(Image $image): bool
    {
        return (
            $image['approved']
            || Ctx::$user->can(ApprovalPermission::APPROVE_IMAGE)
            || Ctx::$user->id === $image->owner_id
        );
    }

    public function onImageDownloading(ImageDownloadingEvent $event): void
    {
        /**
         * Deny images upon insufficient permissions.
         **/
        if (!$this->check_permissions($event->image)) {
            throw new PermissionDenied("Access denied");
        }
    }

    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(ApprovalPermission::APPROVE_IMAGE)) {
            if ($event->image['approved'] === true) {
                $event->add_button("Disapprove", "disapprove_image/".$event->image->id);
            } else {
                $event->add_button("Approve", "approve_image/".$event->image->id);
            }

        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        if (in_array("approved:no", $event->search_terms)) {
            $event->add_action("approve-post", "Approve", "a", permission: ApprovalPermission::APPROVE_IMAGE);
        } else {
            $event->add_action("disapprove-post", "Disapprove", permission: ApprovalPermission::APPROVE_IMAGE);
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "approve-post":
                if (Ctx::$user->can(ApprovalPermission::APPROVE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::approve_image($image->id);
                        $total++;
                    }
                    $event->log_action("Approved $total items");
                }
                break;
            case "disapprove-post":
                if (Ctx::$user->can(ApprovalPermission::APPROVE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::disapprove_image($image->id);
                        $total++;
                    }
                    $event->log_action("Disapproved $total items");
                }
                break;
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN approved BOOLEAN NOT NULL DEFAULT FALSE");
            $database->execute("ALTER TABLE images ADD COLUMN approved_by_id INTEGER NULL");
            $database->execute("CREATE INDEX images_approved_idx ON images(approved)");
            $this->set_version(2);
        }

        if ($this->get_version() < 2) {
            $database->standardise_boolean("images", "approved");
            $this->set_version(2);
        }
    }
}
