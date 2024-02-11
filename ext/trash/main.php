<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class TrashConfig
{
    public const VERSION = "ext_trash_version";
}

class Trash extends Extension
{
    /** @var TrashTheme */
    protected Themelet $theme;

    public function get_priority(): int
    {
        // Needs to be early to intercept delete events
        return 10;
    }

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["trash"] = ImagePropType::BOOL;
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;

        if ($event->page_matches("trash_restore/{image_id}", method: "POST", permission: Permissions::VIEW_TRASH)) {
            $image_id = $event->get_iarg('image_id');
            self::set_trash($image_id, false);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    private function check_permissions(Image $image): bool
    {
        global $user;

        if ($image['trash'] === true && !$user->can(Permissions::VIEW_TRASH)) {
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

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $page;

        if (!$this->check_permissions(($event->image))) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link());
        }
    }

    public function onImageDeletion(ImageDeletionEvent $event): void
    {
        if ($event->force !== true && $event->image['trash'] !== true) {
            self::set_trash($event->image->id, true);
            $event->stop_processing = true;
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent == "posts") {
            if ($user->can(Permissions::VIEW_TRASH)) {
                $event->add_nav_link("posts_trash", new Link('/post/list/in%3Atrash/1'), "Trash", null, 60);
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::VIEW_TRASH)) {
            $event->add_link("Trash", search_link(["in:trash"]), 60);
        }
    }

    public const SEARCH_REGEXP = "/^in:trash$/";
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        global $user;

        $matches = [];

        if (is_null($event->term) && $this->no_trash_query($event->context)) {
            $event->add_querylet(new Querylet("trash != :true", ["true" => true]));
        }

        if (is_null($event->term)) {
            return;
        }
        if (preg_match(self::SEARCH_REGEXP, strtolower($event->term), $matches)) {
            if ($user->can(Permissions::VIEW_TRASH)) {
                $event->add_querylet(new Querylet("trash = :true", ["true" => true]));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        global $user;
        if ($event->key === HelpPages::SEARCH) {
            if ($user->can(Permissions::VIEW_TRASH)) {
                $block = new Block();
                $block->header = "Trash";
                $block->body = $this->theme->get_help_html();
                $event->add_block($block);
            }
        }
    }

    /**
     * @param string[] $context
     */
    private function no_trash_query(array $context): bool
    {
        foreach ($context as $term) {
            if (preg_match(self::SEARCH_REGEXP, $term)) {
                return false;
            }
        }
        return true;
    }

    public static function set_trash(int $image_id, bool $trash): void
    {
        global $database;

        $database->execute(
            "UPDATE images SET trash = :trash WHERE id = :id",
            ["trash" => $trash,"id" => $image_id]
        );
    }
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if ($event->image['trash'] === true && $user->can(Permissions::VIEW_TRASH)) {
            $event->add_button("Restore From Trash", "trash_restore/".$event->image->id);
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        global $user;

        if ($user->can(Permissions::VIEW_TRASH) && in_array("in:trash", $event->search_terms)) {
            $event->add_action("bulk_trash_restore", "(U)ndelete", "u");
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
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

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
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
