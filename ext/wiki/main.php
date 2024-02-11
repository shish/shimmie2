<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;
use GQLA\Mutation;

class WikiUpdateEvent extends Event
{
    public User $user;
    public WikiPage $wikipage;

    public function __construct(User $user, WikiPage $wikipage)
    {
        parent::__construct();
        $this->user = $user;
        $this->wikipage = $wikipage;
    }
}

class WikiDeleteRevisionEvent extends Event
{
    public string $title;
    public int $revision;

    public function __construct(string $title, int $revision)
    {
        parent::__construct();
        $this->title = $title;
        $this->revision = $revision;
    }
}

class WikiDeletePageEvent extends Event
{
    public string $title;

    public function __construct(string $title)
    {
        parent::__construct();
        $this->title = $title;
    }
}

#[Type(name: "WikiPage")]
class WikiPage
{
    public int $id;
    public int $owner_id;
    public string $owner_ip;
    #[Field]
    public string $date;
    #[Field]
    public string $title;
    #[Field]
    public int $revision;
    public bool $locked;
    public bool $exists;
    #[Field]
    public string $body;

    /**
     * @param array<string, mixed> $row
     */
    public function __construct(array $row = null)
    {
        //assert(!empty($row));
        global $database;

        if (!is_null($row)) {
            $this->id = (int)$row['id'];
            $this->owner_id = (int)$row['owner_id'];
            $this->owner_ip = $row['owner_ip'];
            $this->date = $row['date'];
            $this->title = $row['title'];
            $this->revision = (int)$row['revision'];
            $this->locked = bool_escape($row['locked']);
            $this->exists = $database->exists("SELECT id FROM wiki_pages WHERE title = :title", ["title" => $this->title]);
            $this->body = $row['body'];
        }
    }

    #[Field(name: "owner")]
    public function get_owner(): User
    {
        return User::by_id($this->owner_id);
    }

    public function is_locked(): bool
    {
        return $this->locked;
    }

    public function exists(): bool
    {
        return $this->exists;
    }
}

abstract class WikiConfig
{
    public const TAG_PAGE_TEMPLATE = "wiki_tag_page_template";
    public const EMPTY_TAGINFO = "wiki_empty_taginfo";
    public const TAG_SHORTWIKIS = "shortwikis_on_tags";
    public const ENABLE_REVISIONS = "wiki_revisions";
}

class Wiki extends Extension
{
    /** @var WikiTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_string(
            WikiConfig::TAG_PAGE_TEMPLATE,
            "{body}

[b]Aliases: [/b][i]{aliases}[/i]
[b]Auto tags: [/b][i]{autotags}[/i]"
        );
        $config->set_default_string(WikiConfig::EMPTY_TAGINFO, "none");
        $config->set_default_bool(WikiConfig::TAG_SHORTWIKIS, false);
        $config->set_default_bool(WikiConfig::ENABLE_REVISIONS, true);
    }

    // Add a block to the Board Config / Setup
    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Wiki");
        $sb->add_bool_option(WikiConfig::ENABLE_REVISIONS, "Enable wiki revisions: ");
        $sb->add_longtext_option(WikiConfig::TAG_PAGE_TEMPLATE, "<br/>Tag page template: ");
        $sb->add_text_option(WikiConfig::EMPTY_TAGINFO, "<br/>Empty list text: ");
        $sb->add_bool_option(WikiConfig::TAG_SHORTWIKIS, "<br/>Show shortwiki entry when searching for a single tag: ");
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version("ext_wiki_version") < 1) {
            $database->create_table("wiki_pages", "
				id SCORE_AIPK,
				owner_id INTEGER NOT NULL,
				owner_ip SCORE_INET NOT NULL,
				date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				title VARCHAR(255) NOT NULL,
				revision INTEGER NOT NULL DEFAULT 1,
				locked BOOLEAN NOT NULL DEFAULT FALSE,
				body TEXT NOT NULL,
				UNIQUE (title, revision),
				FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
			");
            $this->set_version("ext_wiki_version", 3);
        }
        if ($this->get_version("ext_wiki_version") < 2) {
            $database->execute("ALTER TABLE wiki_pages ADD COLUMN
				locked ENUM('Y', 'N') DEFAULT 'N' NOT NULL AFTER REVISION");
            $this->set_version("ext_wiki_version", 2);
        }
        if ($this->get_version("ext_wiki_version") < 3) {
            $database->standardise_boolean("wiki_pages", "locked", true);
            $this->set_version("ext_wiki_version", 3);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("wiki/{title}/{action}", method: "GET")) {
            $title = $event->get_arg('title');
            $action = $event->get_arg('action');

            if($action == "history") {
                $history = $this->get_history($title);
                $this->theme->display_page_history($page, $title, $history);
            } elseif($action == "edit") {
                $content = $this->get_page($title);
                if ($this->can_edit($user, $content)) {
                    $this->theme->display_page_editor($page, $content);
                } else {
                    throw new PermissionDenied("You are not allowed to edit this page");
                }
            }
        }
        if ($event->page_matches("wiki/{title}/{action}", method: "POST")) {
            $title = $event->get_arg('title');
            $action = $event->get_arg('action');

            if($action == "save") {
                $rev = int_escape($event->req_POST('revision'));
                $body = $event->req_POST('body');
                $lock = $user->can(Permissions::WIKI_ADMIN) && ($event->get_POST('lock') == "on");

                if ($this->can_edit($user, $this->get_page($title))) {
                    $wikipage = $this->get_page($title);
                    $wikipage->revision = $rev;
                    $wikipage->body = $body;
                    $wikipage->locked = $lock;
                    send_event(new WikiUpdateEvent($user, $wikipage));
                    $u_title = url_escape($title);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("wiki/$u_title"));
                } else {
                    throw new PermissionDenied("You are not allowed to edit this page");
                }
            } elseif($action == "delete_revision") {
                $content = $this->get_page($title);
                if ($user->can(Permissions::WIKI_ADMIN)) {
                    $revision = int_escape($event->req_POST('revision'));
                    send_event(new WikiDeleteRevisionEvent($title, $revision));
                    $u_title = url_escape($title);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("wiki/$u_title"));
                } else {
                    throw new PermissionDenied("You are not allowed to edit this page");
                }
            } elseif($action == "delete_all") {
                if ($user->can(Permissions::WIKI_ADMIN)) {
                    send_event(new WikiDeletePageEvent($title));
                    $u_title = url_escape($title);
                    $page->set_mode(PageMode::REDIRECT);
                    $page->set_redirect(make_link("wiki/$u_title"));
                }
            }
        } elseif ($event->page_matches("wiki/{title}")) {
            $title = $event->get_arg('title');
            $revision = int_escape($event->get_GET('revision') ?? "-1");
            $content = $this->get_page($title, $revision);
            $this->theme->display_page($page, $content, $this->get_page("wiki:sidebar"));
        } elseif ($event->page_matches("wiki")) {
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("wiki/Index"));
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("wiki", new Link('wiki'), "Wiki");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "wiki") {
            $event->add_nav_link("wiki_rules", new Link('wiki/rules'), "Rules");
            $event->add_nav_link("wiki_help", new Link('ext_doc/wiki'), "Help");
        }
    }

    public function onWikiUpdate(WikiUpdateEvent $event): void
    {
        global $database, $config;
        $wpage = $event->wikipage;

        $exists = $database->exists("SELECT id FROM wiki_pages WHERE title = :title", ["title" => $wpage->title]);

        try {
            if ($config->get_bool(WikiConfig::ENABLE_REVISIONS) || !$exists) {
                $database->execute(
                    "
                        INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, locked, body)
                        VALUES (:owner_id, :owner_ip, now(), :title, :revision, :locked, :body)",
                    ["owner_id" => $event->user->id, "owner_ip" => get_real_ip(),
                    "title" => $wpage->title, "revision" => $wpage->revision, "locked" => $wpage->locked, "body" => $wpage->body]
                );
            } else {
                $database->execute(
                    "
                        UPDATE wiki_pages SET owner_id=:owner_id, owner_ip=:owner_ip, date=now(), locked=:locked, body=:body
                        WHERE title = :title ORDER BY revision DESC LIMIT 1",
                    ["owner_id" => $event->user->id, "owner_ip" => get_real_ip(),
                    "title" => $wpage->title, "locked" => $wpage->locked, "body" => $wpage->body]
                );
            }
        } catch (\Exception $e) {
            throw new UserError("Somebody else edited that page at the same time :-(");
        }
    }

    public function onWikiDeleteRevision(WikiDeleteRevisionEvent $event): void
    {
        global $database;
        $database->execute(
            "DELETE FROM wiki_pages WHERE title=:title AND revision=:rev",
            ["title" => $event->title, "rev" => $event->revision]
        );
    }

    public function onWikiDeletePage(WikiDeletePageEvent $event): void
    {
        global $database;
        $database->execute(
            "DELETE FROM wiki_pages WHERE title=:title",
            ["title" => $event->title]
        );
    }

    /**
     * See if the given user is allowed to edit the given page.
     */
    public static function can_edit(User $user, WikiPage $page): bool
    {
        // admins can edit everything
        if ($user->can(Permissions::WIKI_ADMIN)) {
            return true;
        }

        // anon / user can't ever edit locked pages
        if ($page->is_locked()) {
            return false;
        }

        // anon / user can edit if allowed by config
        if ($user->can(Permissions::EDIT_WIKI_PAGE)) {
            return true;
        }

        return false;
    }

    /**
     * @return array<array{revision: string, date: string}>
     */
    public static function get_history(string $title): array
    {
        global $database;
        // first try and get the actual page
        return $database->get_all(
            "
				SELECT revision, date
				FROM wiki_pages
				WHERE LOWER(title) LIKE LOWER(:title)
				ORDER BY revision DESC
			",
            ["title" => $title]
        );
    }

    #[Query(name: "wiki")]
    public static function get_page(string $title, ?int $revision = null): WikiPage
    {
        global $database;
        // first try and get the actual page
        $row = $database->get_row(
            "
				SELECT *
				FROM wiki_pages
				WHERE LOWER(title) LIKE LOWER(:title)
				AND (:revision = -1 OR revision = :revision)
				ORDER BY revision DESC
			",
            ["title" => $title, "revision" => $revision ?? -1]
        );

        // fall back to wiki:default
        if (empty($row)) {
            $row = $database->get_row("
                SELECT *
                FROM wiki_pages
                WHERE title LIKE :title
                ORDER BY revision DESC
			", ["title" => "wiki:default"]);

            // fall further back to manual
            if (empty($row)) {
                $row = [
                    "id" => -1,
                    "owner_ip" => "0.0.0.0",
                    "date" => "1970-01-01 00:00:00",
                    "revision" => 0,
                    "locked" => false,
                    "body" => "This is a default page for when a page is empty, ".
                        "it can be edited by editing [[wiki:default]].",
                ];
            }

            // correct the default
            global $config;
            $row["title"] = $title;
            $row["owner_id"] = $config->get_int("anon_id", 0);
        }

        assert(!empty($row));

        return new WikiPage($row);
    }

    public static function format_tag_wiki_page(WikiPage $page): string
    {
        global $database, $config;

        $row = $database->get_row("
                SELECT *
                FROM tags
                WHERE tag = :title
                    ", ["title" => $page->title]);

        if (!empty($row)) {
            $template = $config->get_string(WikiConfig::TAG_PAGE_TEMPLATE);

            //CATEGORIES
            if (Extension::is_enabled(TagCategoriesInfo::KEY)) {
                $tagcategories = new TagCategories();
                $tag_category_dict = $tagcategories->getKeyedDict();
            }

            //ALIASES
            $aliases = $database->get_col("
                SELECT oldtag
                FROM aliases
                WHERE newtag = :title
                ORDER BY oldtag ASC
                    ", ["title" => $row["tag"]]);

            if (!empty($aliases)) {
                $template = str_replace("{aliases}", implode(", ", $aliases), $template);
            } else {
                $template = str_replace("{aliases}", $config->get_string(WikiConfig::EMPTY_TAGINFO), $template);
            }

            //Things before this line will be passed through html_escape.
            $template = format_text($template);
            //Things after this line will NOT be escaped!!! Be careful what you add.

            if (Extension::is_enabled(AutoTaggerInfo::KEY)) {
                $auto_tags = $database->get_one("
                    SELECT additional_tags
                    FROM auto_tag
                    WHERE tag = :title
                        ", ["title" => $row["tag"]]);

                if (!empty($auto_tags)) {
                    $auto_tags = Tag::explode($auto_tags);
                    $f_auto_tags = [];

                    $tag_list_t = new TagListTheme();

                    foreach ($auto_tags as $a_tag) {
                        $a_row = $database->get_row("
                            SELECT *
                            FROM tags
                            WHERE tag = :title
                                ", ["title" => $a_tag]);

                        $tag_html = $tag_list_t->return_tag($a_row, $tag_category_dict ?? []);
                        $f_auto_tags[] = $tag_html[1];
                    }

                    $template = str_replace("{autotags}", implode(", ", $f_auto_tags), $template);
                } else {
                    $template = str_replace("{autotags}", format_text($config->get_string(WikiConfig::EMPTY_TAGINFO)), $template);
                }
            }
        }

        //Insert page body AT LAST to avoid replacing its contents with the actions above.
        return str_replace("{body}", format_text($page->body), $template ?? "{body}");
    }
}
