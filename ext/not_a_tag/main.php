<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroCRUD\{ActionColumn, Table, TextColumn};

use function MicroHTML\{emptyHTML};

final class NotATagTable extends Table
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

final class NotATag extends Extension
{
    public const KEY = "not_a_tag";
    public const VERSION_KEY = "ext_notatag_version";

    public function get_priority(): int
    {
        return 30;
    } // before ImageUploadEvent and tag_history

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;
        if ($this->get_version() < 1) {
            $database->create_table("untags", "
				tag VARCHAR(128) NOT NULL PRIMARY KEY,
				redirect VARCHAR(255) NOT NULL
			");
            $this->set_version(1);
        }
    }

    public function onTagSet(TagSetEvent $event): void
    {
        if (Ctx::$user->can(NotATagPermission::IGNORE_INVALID_TAGS)) {
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
        $tags = [];
        foreach ($tags_mixed as $tag) {
            $tags[] = strtolower($tag);
        }

        $pairs = Ctx::$database->get_pairs("SELECT LOWER(tag), redirect FROM untags");
        foreach ($pairs as $tag => $url) {
            // cast to string because PHP automatically turns ["69" => "No sex"]
            // into [69 => "No sex"]
            // @phpstan-ignore-next-line
            if (in_array(strtolower((string)$tag), $tags)) {
                throw new TagSetException("Invalid tag used: $tag", $url);
            }
        }
    }

    /**
     * @param list<tag-string> $tags
     * @return list<tag-string>
     */
    private function strip(array $tags): array
    {
        $untags = Ctx::$database->get_col("SELECT LOWER(tag) FROM untags");

        $ok_tags = [];
        $stripped_tags = [];
        foreach ($tags as $tag) {
            if (!in_array(strtolower($tag), $untags)) {
                $ok_tags[] = $tag;
            } else {
                $stripped_tags[] = $tag;
            }
        }

        if (count($ok_tags) === 0) {
            $ok_tags = ["tagme"];
        }

        if (count($stripped_tags) > 0) {
            Ctx::$page->flash("Invalid tags stripped: " . implode(", ", $stripped_tags));
        }

        return $ok_tags;
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "tags") {
            if (Ctx::$user->can(NotATagPermission::MANAGE_UNTAG_LIST)) {
                $event->add_nav_link(make_link('untag/list'), "UnTags", "untags");
            }
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        $database = Ctx::$database;

        if ($event->page_matches("untag/add", method: "POST", permission: NotATagPermission::MANAGE_UNTAG_LIST)) {
            $database->execute(
                "INSERT INTO untags(tag, redirect) VALUES (:tag, :redirect)",
                ["tag" => $event->POST->req('c_tag'), "redirect" => $event->POST->req('c_redirect')]
            );
            $page->set_redirect(Url::referer_or());
        }
        if ($event->page_matches("untag/remove", method: "POST", permission: NotATagPermission::MANAGE_UNTAG_LIST)) {
            $database->execute(
                "DELETE FROM untags WHERE LOWER(tag) = LOWER(:tag)",
                ["tag" => $event->POST->req('d_tag')]
            );
            $page->flash("Post ban removed");
            $page->set_redirect(Url::referer_or());
        }
        if ($event->page_matches("untag/list")) {
            $t = new NotATagTable($database->raw_db());
            $t->token = Ctx::$user->get_auth_token();
            $t->inputs = $event->GET->toArray();
            $page->set_title("UnTags");
            $page->add_block(new Block(null, emptyHTML($t->table($t->query()), $t->paginator())));
        }
    }
}
