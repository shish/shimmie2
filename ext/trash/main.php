<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<TrashTheme> */
final class Trash extends Extension
{
    public const KEY = "trash";

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
        if ($event->page_matches("trash_restore/{image_id}", method: "POST", permission: TrashPermission::VIEW_TRASH)) {
            $image_id = $event->get_iarg('image_id');
            self::set_trash($image_id, false);
            Ctx::$page->set_redirect(make_link("post/view/".$image_id));
        }
    }

    private function check_permissions(Image $image): bool
    {
        if ($image['trash'] === true && !Ctx::$user->can(TrashPermission::VIEW_TRASH)) {
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
        if (!$this->check_permissions(($event->image))) {
            Ctx::$page->set_redirect(make_link());
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
        if ($event->parent === "posts") {
            if (Ctx::$user->can(TrashPermission::VIEW_TRASH)) {
                $event->add_nav_link(search_link(['in=trash']), "Trash", "trash", order: 60);
            }
        }
    }

    public const SEARCH_REGEXP = "/^in[=:](trash)$/i";
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term) && $this->no_trash_query($event->context)) {
            $event->add_querylet(new Querylet("trash != TRUE"));
        }

        if ($event->matches(self::SEARCH_REGEXP)) {
            if (Ctx::$user->can(TrashPermission::VIEW_TRASH)) {
                $event->add_querylet(new Querylet("trash = TRUE"));
            }
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            if (Ctx::$user->can(TrashPermission::VIEW_TRASH)) {
                $event->add_section("Trash", $this->theme->get_help_html());
            }
        }
    }

    /**
     * @param string[] $context
     */
    private function no_trash_query(array $context): bool
    {
        foreach ($context as $term) {
            if (\Safe\preg_match(self::SEARCH_REGEXP, $term)) {
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
        if ($event->image['trash'] === true && Ctx::$user->can(TrashPermission::VIEW_TRASH)) {
            $event->add_button("Restore From Trash", "trash_restore/".$event->image->id);
        }
    }

    public function onBulkActionBlockBuilding(BulkActionBlockBuildingEvent $event): void
    {
        if (in_array("in:trash", $event->search_terms)) {
            $event->add_action("trash-restore", "(U)ndelete", "u", permission: TrashPermission::VIEW_TRASH);
        }
    }

    public function onBulkAction(BulkActionEvent $event): void
    {
        switch ($event->action) {
            case "trash-restore":
                if (Ctx::$user->can(TrashPermission::VIEW_TRASH)) {
                    $total = 0;
                    foreach ($event->items as $image) {
                        self::set_trash($image->id, false);
                        $total++;
                    }
                    $event->log_action("Restored $total items from trash");
                }
                break;
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN trash BOOLEAN NOT NULL DEFAULT FALSE");
            $database->execute("CREATE INDEX images_trash_idx ON images(trash)");
            $this->set_version(2);
        }
        if ($this->get_version() < 2) {
            $database->standardise_boolean("images", "trash");
            $this->set_version(2);
        }
    }
}
