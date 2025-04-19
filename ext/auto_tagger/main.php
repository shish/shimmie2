<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\{ActionColumn, Table};

final class AutoTaggerTable extends Table
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

final class AddAutoTagEvent extends Event
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

final class DeleteAutoTagEvent extends Event
{
    public function __construct(
        public string $tag
    ) {
        parent::__construct();
    }
}

final class AutoTaggerException extends SCoreException
{
}

final class AddAutoTagException extends SCoreException
{
}

final class AutoTagger extends Extension
{
    public const KEY = "auto_tagger";
    public const VERSION_KEY = "ext_auto_tagger_ver";

    /** @var AutoTaggerTheme */
    protected Themelet $theme;

    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;

        if ($event->page_matches("auto_tag/add", method: "POST", permission: AutoTaggerPermission::MANAGE_AUTO_TAG)) {
            $input = validate_input(["c_tag" => "string", "c_additional_tags" => "string"]);
            send_event(new AddAutoTagEvent($input['c_tag'], $input['c_additional_tags']));
            $page->set_redirect(make_link("auto_tag/list"));
        }

        if ($event->page_matches("auto_tag/remove", method: "POST", permission: AutoTaggerPermission::MANAGE_AUTO_TAG)) {
            $input = validate_input(["d_tag" => "string"]);
            send_event(new DeleteAutoTagEvent($input['d_tag']));
            $page->set_redirect(make_link("auto_tag/list"));
        }

        if ($event->page_matches("auto_tag/list")) {
            $t = new AutoTaggerTable(Ctx::$database->raw_db());
            $t->token = Ctx::$user->get_auth_token();
            $t->inputs = $event->GET->toArray();
            $t->size = Ctx::$config->get(AutoTaggerConfig::ITEMS_PER_PAGE);
            if (Ctx::$user->can(AutoTaggerPermission::MANAGE_AUTO_TAG)) {
                $t->create_url = make_link("auto_tag/add");
                $t->delete_url = make_link("auto_tag/remove");
            }
            $this->theme->display_auto_tagtable($t->table($t->query()), $t->paginator());
        }

        if ($event->page_matches("auto_tag/export/auto_tag.csv")) {
            $page->set_data(MimeType::CSV, $this->get_auto_tag_csv(Ctx::$database), filename: "auto_tag.csv");
        }

        if ($event->page_matches("auto_tag/import", method: "POST", permission: AutoTaggerPermission::MANAGE_AUTO_TAG)) {
            if (count($_FILES) > 0) {
                $tmp = $_FILES['auto_tag_file']['tmp_name'];
                $contents = \Safe\file_get_contents($tmp);
                $count = $this->add_auto_tag_csv($contents);
                Log::info(AutoTaggerInfo::KEY, "Imported $count auto-tag definitions from file", "Imported $count auto-tag definitions");
                $page->set_redirect(make_link("auto_tag/list"));
            } else {
                throw new InvalidInput("No File Specified");
            }
        }
    }
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            $event->add_nav_link(make_link('auto_tag/list'), "Auto-Tag", ["auto_tag"]);
        }
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // Create the database tables
        if ($this->get_version() < 1) {
            $database->create_table("auto_tag", "
                    tag VARCHAR(128) NOT NULL PRIMARY KEY,
                    additional_tags VARCHAR(2000) NOT NULL
                ");

            if ($database->get_driver_id() === DatabaseDriverID::PGSQL) {
                $database->execute('CREATE INDEX auto_tag_lower_tag_idx ON auto_tag ((lower(tag)))');
            }
            $this->set_version(1);
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
        $this->add_auto_tag($event->tag, $event->additional_tags);
        Ctx::$page->flash("Added Auto-Tag");
    }

    public function onDeleteAutoTag(DeleteAutoTagEvent $event): void
    {
        $this->remove_auto_tag($event->tag);
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(AutoTaggerPermission::MANAGE_AUTO_TAG)) {
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
            if (count($parts) === 2) {
                assert(is_string($parts[0]));
                assert(is_string($parts[1]));
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
            Log::info(
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

            Log::info(
                AutoTaggerInfo::KEY,
                "Added auto-tag for {$tag} -> {".implode(" ", $additional_tags)."}"
            );
        }
    }

    private function remove_auto_tag(string $tag): void
    {
        global $database;

        $database->execute("DELETE FROM auto_tag WHERE LOWER(tag)=LOWER(:tag)", ["tag" => $tag]);
    }

    /**
     * @param list<tag-string> $tags_mixed
     * @return list<tag-string>
     */
    private function apply_auto_tags(array $tags_mixed): array
    {
        global $database;

        // Convert tags to lowercase set for fast lookup
        $tag_set = array_map('strtolower', $tags_mixed);
        $tag_lookup = array_flip($tag_set);

        // Fetch all auto-tag rules
        $rows = $database->get_all("SELECT tag, additional_tags FROM auto_tag");
        $new_tags = [];

        foreach ($rows as $row) {
            $rule = strtolower($row['tag']);
            $additions = Tag::explode($row['additional_tags']);

            // Handle compound AND logic
            if (str_contains($rule, '+')) {
                $parts = explode('+', $rule);
                $matched = true;

                foreach ($parts as $part) {
                    $part = trim($part);
                    if (str_starts_with($part, '!')) {
                        $check = substr($part, 1);
                        if (isset($tag_lookup[$check])) {
                            $matched = false;
                            break;
                        }
                    } else {
                        if (!isset($tag_lookup[$part])) {
                            $matched = false;
                            break;
                        }
                    }
                }

                if ($matched) {
                    $new_tags = array_merge($new_tags, $additions);
                }
            }
            // Handle basic tag match
            elseif (isset($tag_lookup[$rule])) {
                $new_tags = array_merge($new_tags, $additions);
            }
        }

        // Deduplicate and preserve original
        $all_tags = array_merge($tags_mixed, $new_tags);
        return array_values(array_intersect_key(
            $all_tags,
            array_unique(array_map('strtolower', $all_tags))
        ));
    }
}
