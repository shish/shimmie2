<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\ActionColumn;
use MicroCRUD\TextColumn;
use MicroCRUD\Table;

class NotATagTable extends Table
{
    public function __construct(\FFSPHP\PDO $db)
    {
        parent::__construct($db);
        $this->table = "untags";
        $this->base_query = "SELECT * FROM untags";
        $this->primary_key = "tag";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new TextColumn("tag", "Tag"),
            new TextColumn("redirect", "Redirect"),
            new ActionColumn("tag"),
        ]);
        $this->order_by = ["tag", "redirect"];
        $this->create_url = make_link("untag/add");
        $this->delete_url = make_link("untag/remove");
        $this->table_attrs = ["class" => "zebra form"];
    }
}

class NotATag extends Extension
{
    public function get_priority(): int
    {
        return 30;
    } // before ImageUploadEvent and tag_history

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version("ext_notatag_version") < 1) {
            $database->create_table("untags", "
				tag VARCHAR(128) NOT NULL PRIMARY KEY,
				redirect VARCHAR(255) NOT NULL
			");
            $this->set_version("ext_notatag_version", 1);
        }
    }

    public function onTagSet(TagSetEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->new_tags = $this->strip($event->new_tags);
        } else {
            $this->scan($event->new_tags);
        }
    }

    /**
     * @param string[] $tags_mixed
     */
    private function scan(array $tags_mixed): void
    {
        global $database;

        $tags = [];
        foreach ($tags_mixed as $tag) {
            $tags[] = strtolower($tag);
        }

        $pairs = $database->get_pairs("SELECT LOWER(tag), redirect FROM untags");
        foreach ($pairs as $tag => $url) {
            // cast to string because PHP automatically turns ["69" => "No sex"]
            // into [69 => "No sex"]
            if (in_array(strtolower((string)$tag), $tags)) {
                throw new TagSetException("Invalid tag used: $tag", $url);
            }
        }
    }

    /**
     * @param string[] $tags
     * @return string[]
     */
    private function strip(array $tags): array
    {
        global $database;
        $untags = $database->get_col("SELECT LOWER(tag) FROM untags");

        $ok_tags = [];
        foreach ($tags as $tag) {
            if (!in_array(strtolower($tag), $untags)) {
                $ok_tags[] = $tag;
            }
        }

        if (count($ok_tags) == 0) {
            $ok_tags = ["tagme"];
        }

        return $ok_tags;
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        global $user;
        if ($event->parent === "tags") {
            if ($user->can(Permissions::BAN_IMAGE)) {
                $event->add_nav_link("untags", new Link('untag/list'), "UnTags");
            }
        }
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::BAN_IMAGE)) {
            $event->add_link("UnTags", make_link("untag/list"));
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database, $page, $user;

        if ($event->page_matches("untag/add", method: "POST", permission: Permissions::BAN_IMAGE)) {
            $input = validate_input(["c_tag" => "string", "c_redirect" => "string"]);
            $database->execute(
                "INSERT INTO untags(tag, redirect) VALUES (:tag, :redirect)",
                ["tag" => $input['c_tag'], "redirect" => $input['c_redirect']]
            );
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(referer_or(make_link()));
        }
        if ($event->page_matches("untag/remove", method: "POST", permission: Permissions::BAN_IMAGE)) {
            $input = validate_input(["d_tag" => "string"]);
            $database->execute(
                "DELETE FROM untags WHERE LOWER(tag) = LOWER(:tag)",
                ["tag" => $input['d_tag']]
            );
            $page->flash("Post ban removed");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(referer_or(make_link()));
        }
        if ($event->page_matches("untag/list")) {
            $t = new NotATagTable($database->raw_db());
            $t->token = $user->get_auth_token();
            $t->inputs = $event->GET;
            $this->theme->display_crud("UnTags", $t->table($t->query()), $t->paginator());
        }
    }
}
