<?php declare(strict_types=1);

require_once "config.php";
require_once "events/post_title_set_event.php";

class PostTitles extends Extension
{
    /** @var PostTitlesTheme */
    protected $theme;

    public function get_priority(): int
    {
        return 60;
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        $config->set_default_bool(PostTitlesConfig::DEFAULT_TO_FILENAME, false);
        $config->set_default_bool(PostTitlesConfig::SHOW_IN_WINDOW_TITLE, false);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event)
    {
        global $database;

        if ($this->get_version(PostTitlesConfig::VERSION) < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN title varchar(255) NULL");
            $this->set_version(PostTitlesConfig::VERSION, 1);
        }
    }

    public function onDisplayingImage(DisplayingImageEvent $event)
    {
        global $config, $page;

        if ($config->get_bool(PostTitlesConfig::SHOW_IN_WINDOW_TITLE)) {
            $page->set_title(self::get_title($event->get_image()));
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event)
    {
        global $user;

        $event->add_part($this->theme->get_title_set_html(self::get_title($event->image), $user->can(Permissions::EDIT_IMAGE_TITLE)), 10);
    }

    public function onImageInfoSet(ImageInfoSetEvent $event)
    {
        global $user;

        if ($user->can(Permissions::EDIT_IMAGE_TITLE) && isset($_POST["post_title"])) {
            $title = $_POST["post_title"];
            send_event(new PostTitleSetEvent($event->image, $title));
        }
    }

    public function onPostTitleSet(PostTitleSetEvent $event)
    {
        $this->set_title($event->image->id, $event->title);
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Post Titles");
        $sb->start_table();
        $sb->add_bool_option(PostTitlesConfig::DEFAULT_TO_FILENAME, "Default to filename", true);
        $sb->add_bool_option(PostTitlesConfig::SHOW_IN_WINDOW_TITLE, "Show in window title", true);
        $sb->end_table();
    }

    public function onBulkExport(BulkExportEvent $event)
    {
        $event->fields["title"] = $event->image->title;
    }
    public function onBulkImport(BulkImportEvent $event)
    {
        if (property_exists($event->fields, "title") && $event->fields->title!=null) {
            $this->set_title($event->image->id, $event->fields->title);
        }
    }

    private function set_title(int $image_id, string $title)
    {
        global $database;
        $database->execute("UPDATE images SET title=:title WHERE id=:id", ['title'=>$title, 'id'=>$image_id]);
        log_info("post_titles", "Title for >>{$image_id} set to: ".$title);
    }

    public static function get_title(Image $image): string
    {
        global $config;

        $title = $image->title??"";
        if (empty($title) && $config->get_bool(PostTitlesConfig::DEFAULT_TO_FILENAME)) {
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
