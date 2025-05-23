<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;

/*
Todo:
*Quote buttons on posts
*Move delete and quote buttons away from each other
*Bring us on par with comment extension(post linking, image linking, thumb links, URL autolink)
*Smiley filter, word filter, etc should work with our extension

*/
/**
 * @phpstan-type Thread array{id:int,title:string,sticky:bool,user_name:string,uptodate:string,response_count:int}
 * @phpstan-type Post array{id:int,user_name:string,user_class:string,date:string,message:string}
 * @extends Extension<ForumTheme>
 */
final class Forum extends Extension
{
    public const KEY = "forum";
    public const VERSION_KEY = "forum_version";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // shortcut to latest

        if ($this->get_version() < 1) {
            $database->create_table("forum_threads", "
					id SCORE_AIPK,
					sticky BOOLEAN NOT NULL DEFAULT FALSE,
					title VARCHAR(255) NOT NULL,
					user_id INTEGER NOT NULL,
					date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					uptodate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
					");
            $database->execute("CREATE INDEX forum_threads_date_idx ON forum_threads(date)", []);

            $database->create_table("forum_posts", "
					id SCORE_AIPK,
					thread_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					message TEXT,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
					FOREIGN KEY (thread_id) REFERENCES forum_threads (id) ON UPDATE CASCADE ON DELETE CASCADE
					");
            $database->execute("CREATE INDEX forum_posts_date_idx ON forum_posts(date)", []);

            $this->set_version(3);
        }
        if ($this->get_version() < 2) {
            $database->execute("ALTER TABLE forum_threads ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
            $database->execute("ALTER TABLE forum_posts ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
            $this->set_version(2);
        }
        if ($this->get_version() < 3) {
            $database->standardise_boolean("forum_threads", "sticky");
            $this->set_version(3);
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $database;

        $threads_count = $database->get_one("SELECT COUNT(*) FROM forum_threads WHERE user_id=:user_id", ['user_id' => $event->display_user->id]);
        $posts_count = $database->get_one("SELECT COUNT(*) FROM forum_posts WHERE user_id=:user_id", ['user_id' => $event->display_user->id]);

        $days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;

        $threads_rate = sprintf("%.1f", ($threads_count / $days_old));
        $posts_rate = sprintf("%.1f", ($posts_count / $days_old));

        $event->add_part(emptyHTML("Forum threads: $threads_count, $threads_rate per day"));
        $event->add_part(emptyHTML("Forum posts: $posts_count, $posts_rate per day"));
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('forum/index'), "Forum", category: "forum");
    }

    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        $event->add_link("Forum", make_link("forum/index"));
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $user = Ctx::$user;
        $page = Ctx::$page;
        if ($event->page_matches("forum/index", method: "GET", paged: true)) {
            $pageNumber = $event->get_iarg('page_num', 1) - 1;
            $this->show_last_threads($pageNumber, $user->can(ForumPermission::FORUM_ADMIN));
            if ($user->can(ForumPermission::FORUM_CREATE)) {
                $this->theme->display_new_thread_composer();
            }
        }
        if ($event->page_matches("forum/view/{threadID}", method: "GET", paged: true)) {
            $threadID = $event->get_iarg('threadID');
            $pageNumber = $event->get_iarg('page_num', 1) - 1;

            $this->show_posts($threadID, $pageNumber, $user->can(ForumPermission::FORUM_ADMIN));
            if ($user->can(ForumPermission::FORUM_ADMIN)) {
                $this->theme->add_actions_block($threadID);
            }
            if ($user->can(ForumPermission::FORUM_CREATE)) {
                $this->theme->display_new_post_composer($threadID);
            }
        }
        if ($event->page_matches("forum/new", method: "GET", permission: ForumPermission::FORUM_CREATE)) {
            $this->theme->display_new_thread_composer();
        }
        if ($event->page_matches("forum/create", method: "POST", permission: ForumPermission::FORUM_CREATE)) {
            $errors = $this->sanity_check_new_thread($event->POST);
            if (count($errors) > 0) {
                throw new InvalidInput(implode("<br>", $errors));
            }
            $newThreadID = $this->save_new_thread($user);
            $this->save_new_post($newThreadID, $user);
            $redirectTo = "forum/view/" . $newThreadID . "/1";
            $page->set_redirect(make_link($redirectTo));
        }
        if ($event->page_matches("forum/delete/{threadID}/{postID}", method: "POST", permission: ForumPermission::FORUM_ADMIN)) {
            $threadID = $event->get_iarg('threadID');
            $postID = $event->get_iarg('postID');
            $this->delete_post($postID);
            $page->set_redirect(make_link("forum/view/" . $threadID));
        }
        if ($event->page_matches("forum/nuke/{threadID}", method: "POST", permission: ForumPermission::FORUM_ADMIN)) {
            $threadID = $event->get_iarg('threadID');
            $this->delete_thread($threadID);
            $page->set_redirect(make_link("forum/index"));
        }
        if ($event->page_matches("forum/answer", method: "POST", permission: ForumPermission::FORUM_CREATE)) {
            $threadID = int_escape($event->POST->req("threadID"));
            $errors = $this->sanity_check_new_post($event->POST);
            if (count($errors) > 0) {
                throw new InvalidInput(implode("<br>", $errors));
            }
            $total_pages = $this->get_total_pages_for_thread($threadID);
            $this->save_new_post($threadID, $user);
            $page->set_redirect(make_link("forum/view/" . $threadID . "/" . $total_pages));
        }
    }

    private function get_total_pages_for_thread(int $threadID): int
    {
        $count = Ctx::$database->get_one("
            SELECT COUNT(1) AS count
            FROM forum_posts
            WHERE thread_id = :thread_id
        ", ['thread_id' => $threadID]);

        return (int) ceil($count / Ctx::$config->get(ForumConfig::POSTS_PER_PAGE));
    }

    /**
     * @return string[]
     */
    private function sanity_check_new_thread(QueryArray $data): array
    {
        $errors = [];
        if (empty($data["title"])) {
            $errors[] = "You cannot have an empty title.";
        } elseif (strlen($data["title"]) > 255) {
            $errors[] = "Your title is too long.";
        }

        if (empty($data["message"])) {
            $errors[] = "You cannot have an empty message.";
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function sanity_check_new_post(QueryArray $data): array
    {
        $errors = [];
        if (empty($data["threadID"]) || !is_numeric($data["threadID"])) {
            $errors[] = "No thread ID supplied.";
        }
        if (empty($data["message"])) {
            $errors[] = "You cannot have an empty message.";
        }

        return $errors;
    }

    private function get_thread_title(int $threadID): string
    {
        return Ctx::$database->get_one("SELECT t.title FROM forum_threads AS t WHERE t.id = :id ", ['id' => $threadID]);
    }

    private function show_last_threads(int $pageNumber, bool $showAdminOptions = false): void
    {
        $database = Ctx::$database;
        $threadsPerPage = Ctx::$config->get(ForumConfig::THREADS_PER_PAGE);
        $totalPages = (int) ceil($database->get_one("SELECT COUNT(*) FROM forum_threads") / $threadsPerPage);

        /** @var Thread[] $threads */
        $threads = $database->get_all(
            "SELECT f.id, f.sticky, f.title, f.date, f.uptodate, u.name AS user_name, u.email AS user_email, u.class AS user_class, sum(1) - 1 AS response_count " .
            "FROM forum_threads AS f " .
            "INNER JOIN users AS u " .
            "ON f.user_id = u.id " .
            "INNER JOIN forum_posts AS p " .
            "ON p.thread_id = f.id " .
            "GROUP BY f.id, f.sticky, f.title, f.date, u.name, u.email, u.class " .
            "ORDER BY f.sticky ASC, f.uptodate DESC LIMIT :limit OFFSET :offset",
            ["limit" => $threadsPerPage, "offset" => $pageNumber * $threadsPerPage]
        );

        $this->theme->display_thread_list($threads, $showAdminOptions, $pageNumber + 1, $totalPages);
    }

    private function show_posts(int $threadID, int $pageNumber, bool $showAdminOptions = false): void
    {
        global $database;

        $result = Ctx::$database->get_one("SELECT COUNT(*) FROM forum_threads WHERE id=:id", ['id' => $threadID]);
        if ($result !== 1) {
            throw new ObjectNotFound("Thread not found");
        }

        $postsPerPage = Ctx::$config->get(ForumConfig::POSTS_PER_PAGE);
        $totalPages = (int) ceil($database->get_one("SELECT COUNT(*) FROM forum_posts WHERE thread_id = :id", ['id' => $threadID]) / $postsPerPage);
        $threadTitle = $this->get_thread_title($threadID);

        $posts = $database->get_all(
            "SELECT p.id, p.date, p.message, u.name as user_name, u.email AS user_email, u.class AS user_class " .
            "FROM forum_posts AS p " .
            "INNER JOIN users AS u " .
            "ON p.user_id = u.id " .
            "WHERE thread_id = :thread_id " .
            "ORDER BY p.date ASC " .
            "LIMIT :limit OFFSET :offset",
            ["thread_id" => $threadID, "offset" => $pageNumber * $postsPerPage, "limit" => $postsPerPage]
        );
        $this->theme->display_thread($posts, $showAdminOptions, $threadTitle, $threadID, $pageNumber + 1, $totalPages);
    }

    private function save_new_thread(User $user): int
    {
        $title = $_POST["title"];
        $sticky = !empty($_POST["sticky"]);

        global $database;
        $database->execute(
            "
				INSERT INTO forum_threads
				(title, sticky, user_id, date, uptodate)
				VALUES
				(:title, :sticky, :user_id, now(), now())",
            ['title' => $title, 'sticky' => $sticky, 'user_id' => $user->id]
        );

        $threadID = $database->get_last_insert_id("forum_threads_id_seq");

        Log::info("forum", "Thread {$threadID} created by {$user->name}");

        return $threadID;
    }

    private function save_new_post(int $threadID, User $user): void
    {
        $userID = $user->id;
        $message = $_POST["message"];

        $max_characters = Ctx::$config->get(ForumConfig::MAX_CHARS_PER_POST);
        $message = substr($message, 0, $max_characters);

        global $database;
        $database->execute("
			INSERT INTO forum_posts (thread_id, user_id, date, message)
			VALUES (:thread_id, :user_id, now(), :message)
		", ['thread_id' => $threadID, 'user_id' => $userID, 'message' => $message]);

        $postID = $database->get_last_insert_id("forum_posts_id_seq");

        Log::info("forum", "Post {$postID} created by {$user->name}");

        $database->execute("UPDATE forum_threads SET uptodate=now() WHERE id=:id", ['id' => $threadID]);
    }

    private function delete_thread(int $threadID): void
    {
        Ctx::$database->execute("DELETE FROM forum_threads WHERE id = :id", ['id' => $threadID]);
        Ctx::$database->execute("DELETE FROM forum_posts WHERE thread_id = :thread_id", ['thread_id' => $threadID]);
    }

    private function delete_post(int $postID): void
    {
        Ctx::$database->execute("DELETE FROM forum_posts WHERE id = :id", ['id' => $postID]);
    }
}
