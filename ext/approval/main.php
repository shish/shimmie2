<?php

abstract class ApprovalConfig
{
    const VERSION = "ext_approval_version";
    const IMAGES = "approve_images";
    const COMMENTS = "approve_comments";
}

class Approval extends Extension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        $config->set_default_bool(ApprovalConfig::IMAGES, false);
        $config->set_default_bool(ApprovalConfig::COMMENTS, false);

        if ($config->get_int(ApprovalConfig::VERSION) < 1) {
            $this->install();
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("approve_image") && $user->can(Permissions::APPROVE_IMAGE)) {
            // Try to get the image ID
            $image_id = int_escape($event->get_arg(0));
            if (empty($image_id)) {
                $image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
            }
            if (empty($image_id)) {
                throw new SCoreException("Can not approve image: No valid Image ID given.");
            }

            self::approve_image($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    public function onAdminBuilding(AdminBuildingEvent $event)
    {
        $this->theme->display_admin_form();
    }

    public function onAdminAction(AdminActionEvent $event)
    {
        global $database, $user;

        $action = $event->action;
        $event->redirect = true;
        if($action==="approval") {
            $approval_action = $_POST["approval_action"];
            switch ($approval_action) {
                case "approve_all":
                    $database->set_timeout(300000); // These updates can take a little bit
                    $database->execute($database->scoreql_to_sql(
                        "UPDATE images SET approved = SCORE_BOOL_Y, approved_by_id = :approved_by_id WHERE approved = SCORE_BOOL_N"),
                        ["approved_by_id"=>$user->id]
                    );
                    break;
                case "de_approve_all":
                    $database->set_timeout(300000); // These updates can take a little bit
                    $database->execute($database->scoreql_to_sql(
                        "UPDATE images SET approved = SCORE_BOOL_N, approved_by_id = NULL WHERE approved = SCORE_BOOL_Y"));
                    break;
                default:

                    break;
            }
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $user, $page;

        if ($event->image->approved===false && !$user->can(Permissions::APPROVE_IMAGE)) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/list"));
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if($event->parent=="posts") {
            if($user->can(Permissions::APPROVE_IMAGE)) {
                $event->add_nav_link("posts_unapproved", new Link('/post/list/approved%3Ano/1'), "Pending Approval",null, 60);
            }
        }
    }


    const SEARCH_REGEXP = "/^approved:(yes|no)/";
    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        global $user, $database;

        $matches = [];

        if (is_null($event->term) && $this->no_approval_query($event->context)) {
            $event->add_querylet(new Querylet($database->scoreql_to_sql("approved = SCORE_BOOL_Y ")));
        }


        if (preg_match(self::SEARCH_REGEXP, strtolower($event->term), $matches)) {
            if ($user->can(Permissions::APPROVE_IMAGE)&&$matches[1]=="no") {
                $event->add_querylet(new Querylet($database->scoreql_to_sql("approved = SCORE_BOOL_N ")));
            } else {
                $event->add_querylet(new Querylet($database->scoreql_to_sql("approved = SCORE_BOOL_Y ")));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        global $user;
        if ($event->key===HelpPages::SEARCH) {
            if ($user->can(Permissions::APPROVE_IMAGE)) {
                $block = new Block();
                $block->header = "Approval";
                $block->body = $this->theme->get_help_html();
                $event->add_block($block);
            }
        }
    }


    private function no_approval_query(array $context): bool
    {
        foreach ($context as $term) {
            if (preg_match(self::SEARCH_REGEXP, $term)) {
                return false;
            }
        }
        return true;
    }

    public static function approve_image($image_id)
    {
        global $database, $user;

        $database->execute($database->scoreql_to_sql(
            "UPDATE images SET approved = SCORE_BOOL_Y, approved_by_id = :approved_by_id WHERE id = :id AND approved = SCORE_BOOL_N"),
            ["approved_by_id"=>$user->id, "id"=>$image_id]
        );
    }
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($event->image->approved===false && $user->can(Permissions::APPROVE_IMAGE)) {
            $event->add_part($this->theme->get_image_admin_html($event->image->id));
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::APPROVE_IMAGE)&&in_array("approved:no", $event->search_terms)) {
            $event->add_action("bulk_approve_image", "Approve", "a");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user;

        switch ($event->action) {
            case "bulk_approve_image":
                if ($user->can(Permissions::APPROVE_IMAGE)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::approve_image($image->id);
                        $total++;
                    }
                    flash_message("Approved $total items");
                }
                break;
        }
    }


    private function install()
    {
        global $database, $config;

        if ($config->get_int(ApprovalConfig::VERSION) < 1) {
            $database->Execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN approved SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N"
            ));
            $database->Execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN approved_by_id INTEGER NULL"
            ));

            $database->Execute("CREATE INDEX images_approved_idx ON images(approved)");
            $config->set_int(ApprovalConfig::VERSION, 1);
        }
    }
}
