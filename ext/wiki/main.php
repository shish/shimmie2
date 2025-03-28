<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;

final class WikiUpdateEvent extends Event
{
    public function __construct(
        public User $user,
        public WikiPage $wikipage
    ) {
        parent::__construct();
    }
}

final class WikiDeleteRevisionEvent extends Event
{
    public function __construct(
        public string $title,
        public int $revision
    ) {
        parent::__construct();
    }
}

final class WikiDeletePageEvent extends Event
{
    public function __construct(
        public string $title
    ) {
        parent::__construct();
    }
}

#[Type(name: "WikiPage")]
final class WikiPage
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
    public function __construct(?array $row = null)
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

final class Wiki extends Extension
{
    public const KEY = "wiki";
    /** @var WikiTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        if ($this->get_version() < 1) {
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
            $this->set_version(3);
        }
        if ($this->get_version() < 2) {
            $database->execute("ALTER TABLE wiki_pages ADD COLUMN
				locked ENUM('Y', 'N') DEFAULT 'N' NOT NULL AFTER REVISION");
            $this->set_version(2);
        }
        if ($this->get_version() < 3) {
            $database->standardise_boolean("wiki_pages", "locked", true);
            $this->set_version(3);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        $user = Ctx::$user;

        if ($event->page_matches("wiki/{title}/{action}", method: "GET")) {
            $title = $event->get_arg('title');
            $action = $event->get_arg('action');

            if ($action === "history") {
                $history = self::get_history($title);
                $this->theme->display_page_history($title, $history);
            } elseif ($action === "edit") {
                $content = self::get_page($title);
                if (self::can_edit($user, $content)) {
                    $this->theme->display_page_editor($content);
                } else {
                    throw new PermissionDenied("You are not allowed to edit this page");
                }
            }
        }
        if ($event->page_matches("wiki/{title}/{action}", method: "POST")) {
            $title = $event->get_arg('title');
            $action = $event->get_arg('action');

            if ($action === "edit") {
                // we're only here because making a form do a GET request is a
                // pain, so we accept the POST and do a GET redirect
                $page->set_redirect(make_link("wiki/$title/edit"));
            } elseif ($action === "save") {
                $rev = int_escape($event->req_POST('revision'));
                $body = $event->req_POST('body');
                $lock = $user->can(WikiPermission::ADMIN) && ($event->get_POST('lock') == "on");

                if (self::can_edit($user, self::get_page($title))) {
                    $wikipage = self::get_page($title);
                    $wikipage->revision = $rev;
                    $wikipage->body = $body;
                    $wikipage->locked = $lock;
                    send_event(new WikiUpdateEvent($user, $wikipage));
                    $u_title = url_escape($title);
                    $page->set_redirect(make_link("wiki/$u_title"));
                } else {
                    throw new PermissionDenied("You are not allowed to edit this page");
                }
            } elseif ($action === "delete_revision") {
                $content = self::get_page($title);
                if ($user->can(WikiPermission::ADMIN)) {
                    $revision = int_escape($event->req_POST('revision'));
                    send_event(new WikiDeleteRevisionEvent($title, $revision));
                    $u_title = url_escape($title);
                    $page->set_redirect(make_link("wiki/$u_title"));
                } else {
                    throw new PermissionDenied("You are not allowed to edit this page");
                }
            } elseif ($action === "delete_all") {
                if ($user->can(WikiPermission::ADMIN)) {
                    send_event(new WikiDeletePageEvent($title));
                    $u_title = url_escape($title);
                    $page->set_redirect(make_link("wiki/$u_title"));
                }
            }
        } elseif ($event->page_matches("wiki/{title}")) {
            $title = $event->get_arg('title');
            if ($title === "wiki:list") {
                $this->theme->display_list_page(self::get_page("wiki:sidebar"));
            } else {
                $revision = int_escape($event->get_GET('revision') ?? "-1");
                $content = self::get_page($title, $revision);
                $this->theme->display_page($content, self::get_page("wiki:sidebar"));
            }
        } elseif ($event->page_matches("wiki")) {
            $page->set_redirect(make_link("wiki/Index"));
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('wiki'), "Wiki", ["wiki"], "wiki");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "wiki") {
            $event->add_nav_link(make_link('wiki/rules'), "Rules");
            $event->add_nav_link(make_link('ext_doc/wiki'), "Help");
            $event->add_nav_link(make_link('wiki/wiki:list'), "Page list");
        }
    }

    public function onWikiUpdate(WikiUpdateEvent $event): void
    {
        $wpage = $event->wikipage;

        $exists = Ctx::$database->exists("SELECT id FROM wiki_pages WHERE title = :title", ["title" => $wpage->title]);

        try {
            if (Ctx::$config->get(WikiConfig::ENABLE_REVISIONS) || !$exists) {
                Ctx::$database->execute(
                    "
                        INSERT INTO wiki_pages(owner_id, owner_ip, date, title, revision, locked, body)
                        VALUES (:owner_id, :owner_ip, now(), :title, :revision, :locked, :body)",
                    ["owner_id" => $event->user->id, "owner_ip" => Network::get_real_ip(),
                    "title" => $wpage->title, "revision" => $wpage->revision, "locked" => $wpage->locked, "body" => $wpage->body]
                );
            } else {
                Ctx::$database->execute(
                    "
                        UPDATE wiki_pages SET owner_id=:owner_id, owner_ip=:owner_ip, date=now(), locked=:locked, body=:body
                        WHERE title = :title ORDER BY revision DESC LIMIT 1",
                    ["owner_id" => $event->user->id, "owner_ip" => Network::get_real_ip(),
                    "title" => $wpage->title, "locked" => $wpage->locked, "body" => $wpage->body]
                );
            }
        } catch (\Exception $e) {
            throw new UserError("Somebody else edited that page at the same time :-(");
        }
    }

    public function onWikiDeleteRevision(WikiDeleteRevisionEvent $event): void
    {
        Ctx::$database->execute(
            "DELETE FROM wiki_pages WHERE title=:title AND revision=:rev",
            ["title" => $event->title, "rev" => $event->revision]
        );
    }

    public function onWikiDeletePage(WikiDeletePageEvent $event): void
    {
        Ctx::$database->execute(
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
        if ($user->can(WikiPermission::ADMIN)) {
            return true;
        }

        // anon / user can't ever edit locked pages
        if ($page->is_locked()) {
            return false;
        }

        // anon / user can edit if allowed by config
        if ($user->can(WikiPermission::EDIT_WIKI_PAGE)) {
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
        // first try and get the actual page
        $row = Ctx::$database->get_row(
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
            $row = Ctx::$database->get_row("
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
            $row["title"] = $title;
            $row["owner_id"] = Ctx::$config->req(UserAccountsConfig::ANON_ID);
        }

        return new WikiPage($row);
    }
}
