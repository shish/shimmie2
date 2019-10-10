<?php

abstract class TrashConfig
{
    const VERSION = "ext_trash_version";
}

class Trash extends Extension
{
    public function get_priority(): int
    {
        // Needs to be early to intercept delete events
        return 10;
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        if ($config->get_int(TrashConfig::VERSION) < 1) {
            $this->install();
        }
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("trash_restore") && $user->can(Permissions::VIEW_TRASH)) {
            // Try to get the image ID
            $image_id = int_escape($event->get_arg(0));
            if (empty($image_id)) {
                $image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
            }
            if (empty($image_id)) {
                throw new SCoreException("Can not restore image: No valid Image ID given.");
            }

            self::set_trash($image_id, false);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $user, $page;

        if ($event->image->trash===true && !$user->can(Permissions::VIEW_TRASH)) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/list"));
        }
    }

    public function onImageDeletion(ImageDeletionEvent $event)
    {
        if ($event->force!==true && $event->image->trash!==true) {
            self::set_trash($event->image->id, true);
            $event->stop_processing = true;
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event)
    {
        global $user;
        if($event->parent=="posts") {
            if($user->can(Permissions::VIEW_TRASH)) {
                $event->add_nav_link("posts_trash", new Link('/post/list/in%3Atrash/1'), "Trash",null, 60);
            }
        }
    }


    const SEARCH_REGEXP = "/^in:trash$/";
    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        global $user, $database;

        $matches = [];

        if (is_null($event->term) && $this->no_trash_query($event->context)) {
            $event->add_querylet(new Querylet($database->scoreql_to_sql("trash = SCORE_BOOL_N ")));
        }


        if (preg_match(self::SEARCH_REGEXP, strtolower($event->term), $matches)) {
            if ($user->can(Permissions::VIEW_TRASH)) {
                $event->add_querylet(new Querylet($database->scoreql_to_sql("trash = SCORE_BOOL_Y ")));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event)
    {
        global $user;
        if ($event->key===HelpPages::SEARCH) {
            if ($user->can(Permissions::VIEW_TRASH)) {
                $block = new Block();
                $block->header = "Trash";
                $block->body = $this->theme->get_help_html();
                $event->add_block($block);
            }
        }
    }


    private function no_trash_query(array $context): bool
    {
        foreach ($context as $term) {
            if (preg_match(self::SEARCH_REGEXP, $term)) {
                return false;
            }
        }
        return true;
    }

    public static function set_trash($image_id, $trash)
    {
        global $database;

        $database->execute(
            "UPDATE images SET trash = :trash WHERE id = :id",
            ["trash"=>$database->scoresql_value_prepare($trash),"id"=>$image_id]
        );
    }
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event)
    {
        global $user;
        if ($event->image->trash===true && $user->can(Permissions::VIEW_TRASH)) {
            $event->add_part($this->theme->get_image_admin_html($event->image->id));
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event)
    {
        global $user;

        if ($user->can(Permissions::VIEW_TRASH)&&in_array("in:trash", $event->search_terms)) {
            $event->add_action("bulk_trash_restore", "(U)ndelete", "u");
        }
    }

    public function onBulkAction(BulkActionEvent $event)
    {
        global $user;

        switch ($event->action) {
            case "bulk_trash_restore":
                if ($user->can(Permissions::VIEW_TRASH)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::set_trash($image->id, false);
                        $total++;
                    }
                    flash_message("Restored $total items from trash");
                }
                break;
        }
    }


    private function install()
    {
        global $database, $config;

        if ($config->get_int(TrashConfig::VERSION) < 1) {
            $database->Execute($database->scoreql_to_sql(
                "ALTER TABLE images ADD COLUMN trash SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N"
            ));
            $database->Execute("CREATE INDEX images_trash_idx ON images(trash)");
            $config->set_int(TrashConfig::VERSION, 1);
        }
    }
}
