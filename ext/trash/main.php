<?php declare(strict_types=1);

abstract class TrashConfig
{
    const VERSION = "ext_trash_version";
}

class Trash extends Extension
{
    /** @var TrashTheme */
    protected ?Themelet $theme;

    public function get_priority(): int
    {
        // Needs to be early to intercept delete events
        return 10;
    }

    public function onInitExt(InitExtEvent $event)
    {
        Image::$bool_props[] = "trash";
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches("trash_restore") && $user->can(Permissions::VIEW_TRASH)) {
            // Try to get the image ID
            if ($event->count_args() >= 1) {
                $image_id = int_escape($event->get_arg(0));
            } elseif (isset($_POST['image_id'])) {
                $image_id = $_POST['image_id'];
            } else {
                throw new SCoreException("Can not restore post: No valid Post ID given.");
            }

            self::set_trash($image_id, false);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    private function check_permissions(Image $image): bool
    {
        global $user;

        if ($image->trash===true && !$user->can(Permissions::VIEW_TRASH)) {
            return false;
        }
        return true;
    }

    public function onImageDownloading(ImageDownloadingEvent $event)
    {
        /**
         * Deny images upon insufficient permissions.
         **/
        if (!$this->check_permissions($event->image)) {
            throw new SCoreException("Access denied");
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $page;

        if (!$this->check_permissions(($event->image))) {
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
        if ($event->parent=="posts") {
            if ($user->can(Permissions::VIEW_TRASH)) {
                $event->add_nav_link("posts_trash", new Link('/post/list/in%3Atrash/1'), "Trash", null, 60);
            }
        }
    }

    const SEARCH_REGEXP = "/^in:trash$/";
    public function onSearchTermParse(SearchTermParseEvent $event)
    {
        global $user;

        $matches = [];

        if (is_null($event->term) && $this->no_trash_query($event->context)) {
            $event->add_querylet(new Querylet("trash != :true", ["true"=>true]));
        }

        if (is_null($event->term)) {
            return;
        }
        if (preg_match(self::SEARCH_REGEXP, strtolower($event->term), $matches)) {
            if ($user->can(Permissions::VIEW_TRASH)) {
                $event->add_querylet(new Querylet("trash = :true", ["true"=>true]));
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

    public static function set_trash(int $image_id, bool $trash)
    {
        global $database;

        $database->execute(
            "UPDATE images SET trash = :trash WHERE id = :id",
            ["trash"=>$trash,"id"=>$image_id]
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
        global $page, $user;

        switch ($event->action) {
            case "bulk_trash_restore":
                if ($user->can(Permissions::VIEW_TRASH)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::set_trash($image->id, false);
                        $total++;
                    }
                    $page->flash("Restored $total items from trash");
                }
                break;
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version(TrashConfig::VERSION) < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN trash BOOLEAN NOT NULL DEFAULT FALSE");
            $database->execute("CREATE INDEX images_trash_idx ON images(trash)");
            $this->set_version(TrashConfig::VERSION, 2);
        }
        if ($this->get_version(TrashConfig::VERSION) < 2) {
            $database->standardise_boolean("images", "trash");
            $this->set_version(TrashConfig::VERSION, 2);
        }
    }
}
