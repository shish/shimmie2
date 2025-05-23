<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "events/post_title_set_event.php";

/** @extends Extension<PostTitlesTheme> */
final class PostTitles extends Extension
{
    public const KEY = "post_titles";

    public function get_priority(): int
    {
        return 60;
    }

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["title"] = ImagePropType::STRING;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN title varchar(255) NULL");
            $this->set_version(1);
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if (Ctx::$config->get(PostTitlesConfig::SHOW_IN_WINDOW_TITLE)) {
            Ctx::$page->set_title(self::get_title($event->image));
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $event->add_part(
            $this->theme->get_title_set_html(
                self::get_title($event->image),
                Ctx::$user->can(PostTitlesPermission::EDIT_IMAGE_TITLE)
            ),
            10
        );
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $title = $event->get_param('title');
        if (Ctx::$user->can(PostTitlesPermission::EDIT_IMAGE_TITLE) && !is_null($title)) {
            send_event(new PostTitleSetEvent($event->image, $title));
        }
    }

    public function onPostTitleSet(PostTitleSetEvent $event): void
    {
        $this->set_title($event->image->id, $event->title);
    }

    public function onBulkExport(BulkExportEvent $event): void
    {
        $event->fields["title"] = $event->image['title'];
    }

    public function onBulkImport(BulkImportEvent $event): void
    {
        if (array_key_exists("title", $event->fields) && $event->fields['title'] !== null) {
            $this->set_title($event->image->id, $event->fields['title']);
        }
    }

    private function set_title(int $image_id, string $title): void
    {
        Ctx::$database->execute("UPDATE images SET title=:title WHERE id=:id", ['title' => $title, 'id' => $image_id]);
        Log::info("post_titles", "Title for >>{$image_id} set to: ".$title);
    }

    public static function get_title(Image $image): string
    {
        $title = $image['title'] ?? "";
        if (empty($title) && Ctx::$config->get(PostTitlesConfig::DEFAULT_TO_FILENAME)) {
            $info = pathinfo($image->filename);
            if (array_key_exists("extension", $info)) {
                $title = basename($image->filename, '.' . $info['extension']);
            } else {
                $title = $image->filename;
            }
        }
        return $title;
    }
}
