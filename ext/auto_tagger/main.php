<?php

declare(strict_types=1);

namespace Shimmie2;

require_once 'config.php';

use MicroCRUD\ActionColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

class AutoTaggerTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "auto_tagger";
        $this->base_query = "SELECT * FROM auto_tag";
        $this->primary_key = "tag";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new AutoCompleteColumn("tag", "Tag"),
            new AutoCompleteColumn("additional_tags", "Additional Tags"),
            new ActionColumn("tag"),
        ]);
        $this->order_by = ["tag"];
        $this->table_attrs = ["class" => "zebra form"];
    }
}

class AddAutoTagEvent extends Event
{
    public string $tag;
    public string $additional_tags;

    public function __construct(string $tag, string $additional_tags)
    {
        parent::__construct();
        $this->tag = trim($tag);
        $this->additional_tags = trim($additional_tags);
    }
}

class DeleteAutoTagEvent extends Event
{
    public string $tag;

    public function __construct(string $tag)
    {
        parent::__construct();
        $this->tag = $tag;
    }
}

class AutoTaggerException extends SCoreException
{
}

class AddAutoTagException extends SCoreException
{
}

class AutoTagger extends Extension
{
    /** @var AutoTaggerTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $database, $page, $user;

        if ($event->page_matches("auto_tag/add", method: "POST", permission: Permissions::MANAGE_AUTO_TAG)) {
            $input = validate_input(["c_tag" => "string", "c_additional_tags" => "string"]);
            send_event(new AddAutoTagEvent($input['c_tag'], $input['c_additional_tags']));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("auto_tag/list"));
        }
        if ($event->page_matches("auto_tag/remove", method: "POST", permission: Permissions::MANAGE_AUTO_TAG)) {
            $input = validate_input(["d_tag" => "string"]);
            send_event(new DeleteAutoTagEvent($input['d_tag']));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("auto_tag/list"));
        }
        if ($event->page_matches("auto_tag/list")) {
            $t = new AutoTaggerTable($database->raw_db());
            $t->token = $user->get_auth_token();
            $t->inputs = $event->GET;
            $t->size = $config->get_int(AutoTaggerConfig::ITEMS_PER_PAGE, 30);
            if ($user->can(Permissions::MANAGE_AUTO_TAG)) {
                $t->create_url = make_link("auto_tag/add");
                $t->delete_url = make_link("auto_tag/remove");
            }
            $this->theme->display_auto_tagtable($t->table($t->query()), $t->paginator());
        }
        if ($event->page_matches("auto_tag/export/auto_tag.csv")) {
            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::CSV);
            $page->set_filename("auto_tag.csv");
            $page->set_data($this->get_auto_tag_csv($database));
        }
        if ($event->page_matches("auto_tag/import", method: "POST", permission: Permissions::MANAGE_AUTO_TAG)) {
            if (count($_FILES) > 0) {
                $tmp = $_FILES['auto_tag_file']['tmp_name'];
                $contents = \Safe\file_get_contents($tmp);
                $count = $this->add_auto_tag_csv($contents);
                log_info(AutoTaggerInfo::KEY, "Imported $count auto-tag definitions from file from file", "Imported $count auto-tag definitions");
                $page->set_mode(PageMode::REDIRECT);
                $page->set_redirect(make_link("auto_tag/list"));
            } else {
                $this->theme->display_error(400, "No File Specified", "You have to upload a file");
            }
        }
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "tags") {
            $event->add_nav_link("auto_tag", new Link('auto_tag/list'), "Auto-Tag", NavLink::is_active(["auto_tag"]));
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // Create the database tables
        if ($this->get_version(AutoTaggerConfig::VERSION) < 1) {
            $database->create_table("auto_tag", "
                    tag VARCHAR(128) NOT NULL PRIMARY KEY,
                    additional_tags VARCHAR(2000) NOT NULL
                ");

            if ($database->get_driver_id() == DatabaseDriverID::PGSQL) {
                $database->execute('CREATE INDEX auto_tag_lower_tag_idx ON auto_tag ((lower(tag)))');
            }
            $this->set_version(AutoTaggerConfig::VERSION, 1);

            log_info(AutoTaggerInfo::KEY, "extension installed");
        }
    }

    public function onTagSet(TagSetEvent $event): void
    {
        $results = $this->apply_auto_tags($event->new_tags);
        if (!empty($results)) {
            $event->new_tags = $results;
        }
    }

    public function onAddAutoTag(AddAutoTagEvent $event): void
    {
        global $page;
        $this->add_auto_tag($event->tag, $event->additional_tags);
        $page->flash("Added Auto-Tag");
    }

    public function onDeleteAutoTag(DeleteAutoTagEvent $event): void
    {
        $this->remove_auto_tag($event->tag);
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::MANAGE_AUTO_TAG)) {
            $event->add_link("Auto-Tag Editor", make_link("auto_tag/list"));
        }
    }

    private function get_auto_tag_csv(Database $database): string
    {
        $csv = "";
        $pairs = $database->get_pairs("SELECT tag, additional_tags FROM auto_tag ORDER BY tag");
        foreach ($pairs as $old => $new) {
            assert(is_string($new));
            $csv .= "\"$old\",\"$new\"\n";
        }
        return $csv;
    }

    private function add_auto_tag_csv(string $csv): int
    {
        $csv = str_replace("\r", "\n", $csv);
        $i = 0;
        foreach (explode("\n", $csv) as $line) {
            $parts = str_getcsv($line);
            if (count($parts) == 2) {
                send_event(new AddAutoTagEvent($parts[0], $parts[1]));
                $i++;
            }
        }
        return $i;
    }

    private function add_auto_tag(string $tag, string $additional_tags): void
    {
        global $database;
        $existing_tags = $database->get_one("SELECT additional_tags FROM auto_tag WHERE LOWER(tag)=LOWER(:tag)", ["tag" => $tag]);
        if (!is_null($existing_tags)) {
            // Auto Tags already exist, so we will append new tags to the existing one
            $tag = Tag::sanitize($tag);
            $additional_tags = Tag::explode($additional_tags);
            $existing_tags = Tag::explode($existing_tags);
            foreach ($additional_tags as $t) {
                if (!in_array(strtolower($t), $existing_tags)) {
                    $existing_tags[] = strtolower($t);
                }
            }

            $database->execute(
                "UPDATE auto_tag set additional_tags=:existing_tags where tag=:tag",
                ["tag" => $tag, "existing_tags" => Tag::implode($existing_tags)]
            );
            log_info(
                AutoTaggerInfo::KEY,
                "Updated auto-tag for {$tag} -> {".implode(" ", $additional_tags)."}"
            );
        } else {
            $tag = Tag::sanitize($tag);
            $additional_tags = Tag::explode($additional_tags);

            $database->execute(
                "INSERT INTO auto_tag(tag, additional_tags) VALUES(:tag, :additional_tags)",
                ["tag" => $tag, "additional_tags" => Tag::implode($additional_tags)]
            );

            log_info(
                AutoTaggerInfo::KEY,
                "Added auto-tag for {$tag} -> {".implode(" ", $additional_tags)."}"
            );
        }
        // Now we apply it to existing items
        $this->apply_new_auto_tag($tag);
    }

    private function apply_new_auto_tag(string $tag): void
    {
        global $database;
        $tag_id = $database->get_one("SELECT id FROM tags WHERE LOWER(tag) = LOWER(:tag)", ["tag" => $tag]);
        if (!empty($tag_id)) {
            $image_ids = $database->get_col_iterable("SELECT image_id FROM  image_tags WHERE tag_id = :tag_id", ["tag_id" => $tag_id]);
            foreach ($image_ids as $image_id) {
                $image_id = (int) $image_id;
                $image = Image::by_id_ex($image_id);
                send_event(new TagSetEvent($image, $image->get_tag_array()));
            }
        }
    }

    private function remove_auto_tag(string $tag): void
    {
        global $database;

        $database->execute("DELETE FROM auto_tag WHERE LOWER(tag)=LOWER(:tag)", ["tag" => $tag]);
    }

    /**
     * @param string[] $tags_mixed
     * @return string[]
     */
    private function apply_auto_tags(array $tags_mixed): array
    {
        global $database;

        while (true) {
            $new_tags = [];
            foreach ($tags_mixed as $tag) {
                $additional_tags = $database->get_one(
                    "SELECT additional_tags FROM auto_tag WHERE LOWER(tag) = LOWER(:input)",
                    ["input" => $tag]
                );

                if (!empty($additional_tags)) {
                    $additional_tags = explode(" ", $additional_tags);
                    $new_tags = array_merge(
                        $new_tags,
                        array_udiff($additional_tags, $tags_mixed, 'strcasecmp')
                    );
                }
            }
            if (empty($new_tags)) {
                break;
            }
            $tags_mixed = array_merge($tags_mixed, $new_tags);
        }

        return array_intersect_key(
            $tags_mixed,
            array_unique(array_map('strtolower', $tags_mixed))
        );
    }

    /**
     * Get the priority for this extension.
     *
     */
    public function get_priority(): int
    {
        return 30;
    }
}
