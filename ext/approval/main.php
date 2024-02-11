<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class ApprovalConfig
{
    public const VERSION = "ext_approval_version";
    public const IMAGES = "approve_images";
    public const COMMENTS = "approve_comments";
}

class Approval extends Extension
{
    /** @var ApprovalTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;

        $config->set_default_bool(ApprovalConfig::IMAGES, false);
        $config->set_default_bool(ApprovalConfig::COMMENTS, false);

        Image::$prop_types["approved"] = ImagePropType::BOOL;
        Image::$prop_types["approved_by_id"] = ImagePropType::INT;
    }

    public function onImageAddition(ImageAdditionEvent $event): void
    {
        global $user, $config;

        if ($config->get_bool(ApprovalConfig::IMAGES) && $user->can(Permissions::BYPASS_IMAGE_APPROVAL)) {
            self::approve_image($event->image->id);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("approve_image/{image_id}", method: "POST", permission: Permissions::APPROVE_IMAGE)) {
            $image_id = int_escape($event->get_arg('image_id'));
            self::approve_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/" . $image_id));
        }

        if ($event->page_matches("disapprove_image/{image_id}", method: "POST", permission: Permissions::APPROVE_IMAGE)) {
            $image_id = int_escape($event->get_arg('image_id'));
            self::disapprove_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Approval");
        $sb->add_bool_option(ApprovalConfig::IMAGES, "Posts: ");
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_form();
    }

    public function onAdminAction(AdminActionEvent $event): void
    {
        global $database, $user;

        $action = $event->action;
        $event->redirect = true;
        if ($action === "approval") {
            $approval_action = $event->params["approval_action"];
            switch ($approval_action) {
                case "approve_all":
                    $database->set_timeout(null); // These updates can take a little bit
                    $database->execute(
                        "UPDATE images SET approved = :true, approved_by_id = :approved_by_id WHERE approved = :false",
                        ["approved_by_id" => $user->id, "true" => true, "false" => false]
                    );
                    break;
                case "disapprove_all":
                    $database->set_timeout(null); // These updates can take a little bit
                    $database->execute(
                        "UPDATE images SET approved = :false, approved_by_id = NULL WHERE approved = :true",
                        ["true" => true, "false" => false]
                    );
                    break;
                default:
                    break;
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $page;

        if (!$this->check_permissions($event->image)) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent == "posts") {
            if ($user->can(Permissions::APPROVE_IMAGE)) {
                $event->add_nav_link("posts_unapproved", new Link('/post/list/approved%3Ano/1'), "Pending Approval", null, 60);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::APPROVE_IMAGE)) {
            $event->add_link("Pending Approval", search_link(["approved:no"]), 60);
        }
    }

    public const SEARCH_REGEXP = "/^approved:(yes|no)/";
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $user, $config;

        if ($config->get_bool(ApprovalConfig::IMAGES)) {
            $matches = [];

            if (is_null($event->term) && $this->no_approval_query($event->context)) {
                $event->add_querylet(new Querylet("approved = :true", ["true" => true]));
            }

            if (is_null($event->term)) {
                return;
            }
            if (preg_match(self::SEARCH_REGEXP, strtolower($event->term), $matches)) {
                if ($user->can(Permissions::APPROVE_IMAGE) && $matches[1] == "no") {
                    $event->add_querylet(new Querylet("approved != :true", ["true" => true]));
                } else {
                    $event->add_querylet(new Querylet("approved = :true", ["true" => true]));
                }
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        global $user, $config;
        if ($event->key === HelpPages::SEARCH) {
            if ($user->can(Permissions::APPROVE_IMAGE) &&  $config->get_bool(ApprovalConfig::IMAGES)) {
                $event->add_block(new Block("Approval", $this->theme->get_help_html()));
            }
        }
    }

    /**
     * @param string[] $context
     */
    private function no_approval_query(array $context): bool
    {
        foreach ($context as $term) {
            if (preg_match(self::SEARCH_REGEXP, $term)) {
                return false;
            }
        }
        return true;
    }

    public static function approve_image(int $image_id): void
    {
        global $database, $user;

        $database->execute(
            "UPDATE images SET approved = :true, approved_by_id = :approved_by_id WHERE id = :id AND approved = :false",
            ["approved_by_id" => $user->id, "id" => $image_id, "true" => true, "false" => false]
        );
    }

    public static function disapprove_image(int $image_id): void
    {
        global $database;

        $database->execute(
            "UPDATE images SET approved = :false, approved_by_id = NULL WHERE id = :id AND approved = :true",
            ["id" => $image_id, "true" => true, "false" => false]
        );
    }

    private function check_permissions(Image $image): bool
    {
        global $user, $config;

        if ($config->get_bool(ApprovalConfig::IMAGES) && $image['approved'] === false && !$user->can(Permissions::APPROVE_IMAGE) && $user->id !== $image->owner_id) {
            return false;
        }
        return true;
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
        global $user, $config;
        if ($user->can(Permissions::APPROVE_IMAGE) && $config->get_bool(ApprovalConfig::IMAGES)) {
            if ($event->image['approved'] === true) {
                $event->add_button("Disapprove", "disapprove_image/".$event->image->id);
            } else {
                $event->add_button("Approve", "approve_image/".$event->image->id);
            }

        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user, $config;

        if ($user->can(Permissions::APPROVE_IMAGE) && $config->get_bool(ApprovalConfig::IMAGES)) {
            if (in_array("approved:no", $event->search_terms)) {
                $event->add_action("bulk_approve_image", "Approve", "a");
            } else {
                $event->add_action("bulk_disapprove_image", "Disapprove");
            }
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        global $page, $user;

        switch ($event->action) {
            case "bulk_approve_image":
                if ($user->can(Permissions::APPROVE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::approve_image($image->id);
                        $total++;
                    }
                    $page->flash("Approved $total items");
                }
                break;
            case "bulk_disapprove_image":
                if ($user->can(Permissions::APPROVE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::disapprove_image($image->id);
                        $total++;
                    }
                    $page->flash("Disapproved $total items");
                }
                break;
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version(ApprovalConfig::VERSION) < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN approved BOOLEAN NOT NULL DEFAULT FALSE");
            $database->execute("ALTER TABLE images ADD COLUMN approved_by_id INTEGER NULL");
            $database->execute("CREATE INDEX images_approved_idx ON images(approved)");
            $this->set_version(ApprovalConfig::VERSION, 2);
        }

        if ($this->get_version(ApprovalConfig::VERSION) < 2) {
            $database->standardise_boolean("images", "approved");
            $this->set_version(ApprovalConfig::VERSION, 2);
        }
    }
}
