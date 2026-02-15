<?php

declare(strict_types=1);

namespace Shimmie2;

class PostTitleSetEvent extends Event
{
    public function __construct(
        public Image $image,
        public string $title
    ) {
        parent::__construct();
    }
}

/** @extends Extension<PostTitlesTheme> */
final class PostTitles extends Extension
{
    public const KEY = "post_titles";

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["title"] = ImagePropType::STRING;
    }

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN title varchar(255) NULL");
            $this->set_version(1);
        }
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        if (Ctx::$config->get(PostTitlesConfig::SHOW_IN_WINDOW_TITLE)) {
            Ctx::$page->set_title(self::get_title($event->image));
        }
    }

    #[EventListener]
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

    #[EventListener]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        $title = $event->get_param('title');
        if (Ctx::$user->can(PostTitlesPermission::EDIT_IMAGE_TITLE) && !is_null($title)) {
            send_event(new PostTitleSetEvent($event->image, $title));
        }
    }

    #[EventListener]
    public function onPostTitleSet(PostTitleSetEvent $event): void
    {
        $this->set_title($event->image->id, $event->title);
    }

    #[EventListener]
    public function onBulkExport(BulkExportEvent $event): void
    {
        $event->fields["title"] = $event->image['title'];
    }

    #[EventListener]
    public function onBulkImport(BulkImportEvent $event): void
    {
        if (property_exists($event->fields, "title") && $event->fields->title !== null) {
            $this->set_title($event->image->id, $event->fields->title);
        }
    }

    #[EventListener(priority: 60)]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^(title)[=:](.*)$/i")) {
            $title = strtolower($matches[2]);

            if (\Safe\preg_match("/^(any|none)$/i", $title)) {
                $not = ($title === "any" ? "NOT" : "");
                $event->add_querylet(new Querylet("images.title IS $not NULL"));
            } else {
                $event->add_querylet(new Querylet('SCORE_ILIKE(images.title, :title)', ["title" => "%$title%"]));
            }
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
