<?php

declare(strict_types=1);

namespace Shimmie2;

/*
Todo:
*Quote buttons on posts
*Move delete and quote buttons away from each other
*Bring us on par with comment extension(post linking, image linking, thumb links, URL autolink)
*Smiley filter, word filter, etc should work with our extension

*/
class Forum extends Extension
{
    /** @var ForumTheme */
    protected Themelet $theme;

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $config, $database;

        // shortcut to latest

        if ($this->get_version("forum_version") < 1) {
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

            $config->set_int("forumTitleSubString", 25);
            $config->set_int("forumThreadsPerPage", 15);
            $config->set_int("forumPostsPerPage", 15);

            $config->set_int("forumMaxCharsPerPost", 512);

            $this->set_version("forum_version", 3);
        }
        if ($this->get_version("forum_version") < 2) {
            $database->execute("ALTER TABLE forum_threads ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
            $database->execute("ALTER TABLE forum_posts ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
            $this->set_version("forum_version", 2);
        }
        if ($this->get_version("forum_version") < 3) {
            $database->standardise_boolean("forum_threads", "sticky");
            $this->set_version("forum_version", 3);
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Forum");
        $sb->add_int_option("forumTitleSubString", "Title max long: ");
        $sb->add_int_option("forumThreadsPerPage", "<br>Threads per page: ");
        $sb->add_int_option("forumPostsPerPage", "<br>Posts per page: ");

        $sb->add_int_option("forumMaxCharsPerPost", "<br>Max chars per post: ");
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        global $database;

        $threads_count = $database->get_one("SELECT COUNT(*) FROM forum_threads WHERE user_id=:user_id", ['user_id' => $event->display_user->id]);
        $posts_count = $database->get_one("SELECT COUNT(*) FROM forum_posts WHERE user_id=:user_id", ['user_id' => $event->display_user->id]);

        $days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;

        $threads_rate = sprintf("%.1f", ($threads_count / $days_old));
        $posts_rate = sprintf("%.1f", ($posts_count / $days_old));

        $event->add_part("Forum threads: $threads_count, $threads_rate per day");
        $event->add_part("Forum posts: $posts_count, $posts_rate per day");
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("forum", new Link('forum/index'), "Forum");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("forum/index", paged: true)) {
            $pageNumber = $event->get_iarg('page_num', 1) - 1;
            $this->show_last_threads($page, $pageNumber, $user->can(Permissions::FORUM_ADMIN));
            if ($user->can(Permissions::FORUM_CREATE)) {
                $this->theme->display_new_thread_composer($page);
            }
        }
        if ($event->page_matches("forum/view/{threadID}", paged: true)) {
            $threadID = $event->get_iarg('threadID');
            $pageNumber = $event->get_iarg('page_num', 1) - 1;
            $errors = $this->sanity_check_viewed_thread($threadID);

            if (count($errors) > 0) {
                throw new InvalidInput(implode("<br>", $errors));
            }

            $this->show_posts($threadID, $pageNumber, $user->can(Permissions::FORUM_ADMIN));
            if ($user->can(Permissions::FORUM_ADMIN)) {
                $this->theme->add_actions_block($page, $threadID);
            }
            if ($user->can(Permissions::FORUM_CREATE)) {
                $this->theme->display_new_post_composer($page, $threadID);
            }
        }
        if ($event->page_matches("forum/new", permission: Permissions::FORUM_CREATE)) {
            $this->theme->display_new_thread_composer($page);
        }
        if ($event->page_matches("forum/create", permission: Permissions::FORUM_CREATE)) {
            $errors = $this->sanity_check_new_thread();

            if (count($errors) > 0) {
                throw new InvalidInput(implode("<br>", $errors));
            }

            $newThreadID = $this->save_new_thread($user);
            $this->save_new_post($newThreadID, $user);
            $redirectTo = "forum/view/" . $newThreadID . "/1";

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link($redirectTo));
        }
        if ($event->page_matches("forum/delete/{threadID}/{postID}", permission: Permissions::FORUM_ADMIN)) {
            $threadID = $event->get_iarg('threadID');
            $postID = $event->get_iarg('postID');
            $this->delete_post($postID);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("forum/view/" . $threadID));
        }
        if ($event->page_matches("forum/nuke/{threadID}", permission: Permissions::FORUM_ADMIN)) {
            $threadID = $event->get_iarg('threadID');
            $this->delete_thread($threadID);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("forum/index"));
        }
        if ($event->page_matches("forum/answer", permission: Permissions::FORUM_CREATE)) {
            $threadID = int_escape($event->req_POST("threadID"));
            $total_pages = $this->get_total_pages_for_thread($threadID);

            $errors = $this->sanity_check_new_post();

            if (count($errors) > 0) {
                throw new InvalidInput(implode("<br>", $errors));
            }
            $this->save_new_post($threadID, $user);

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("forum/view/" . $threadID . "/" . $total_pages));
        }

    }

    private function get_total_pages_for_thread(int $threadID): int
    {
        global $database, $config;
        $result = $database->get_row("
            SELECT COUNT(1) AS count
            FROM forum_posts
            WHERE thread_id = :thread_id
        ", ['thread_id' => $threadID]);

        return (int) ceil($result["count"] / $config->get_int("forumPostsPerPage"));
    }

    /**
     * @return string[]
     */
    private function sanity_check_new_thread(): array
    {
        $errors = [];
        if (!array_key_exists("title", $_POST)) {
            $errors[] = "No title supplied.";
        } elseif (strlen($_POST["title"]) == 0) {
            $errors[] = "You cannot have an empty title.";
        } elseif (strlen($_POST["title"]) > 255) {
            $errors[] = "Your title is too long.";
        }

        if (!array_key_exists("message", $_POST)) {
            $errors[] = "No message supplied.";
        } elseif (strlen($_POST["message"]) == 0) {
            $errors[] = "You cannot have an empty message.";
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function sanity_check_new_post(): array
    {
        $errors = [];
        if (!array_key_exists("threadID", $_POST)) {
            $errors[] = "No thread ID supplied.";
        } elseif (strlen($_POST["threadID"]) == 0) {
            $errors[] = "No thread ID supplied.";
        } elseif (is_numeric($_POST["threadID"])) {
            if (!array_key_exists("message", $_POST)) {
                $errors[] = "No message supplied.";
            } elseif (strlen($_POST["message"]) == 0) {
                $errors[] = "You cannot have an empty message.";
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function sanity_check_viewed_thread(int $threadID): array
    {
        $errors = [];
        if (!$this->threadExists($threadID)) {
            $errors[] = "Inexistent thread.";
        }
        return $errors;
    }

    private function get_thread_title(int $threadID): string
    {
        global $database;
        return $database->get_one("SELECT t.title FROM forum_threads AS t WHERE t.id = :id ", ['id' => $threadID]);
    }

    private function show_last_threads(Page $page, int $pageNumber, bool $showAdminOptions = false): void
    {
        global $config, $database;
        $threadsPerPage = $config->get_int('forumThreadsPerPage', 15);
        $totalPages = (int) ceil($database->get_one("SELECT COUNT(*) FROM forum_threads") / $threadsPerPage);

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

        $this->theme->display_thread_list($page, $threads, $showAdminOptions, $pageNumber + 1, $totalPages);
    }

    private function show_posts(int $threadID, int $pageNumber, bool $showAdminOptions = false): void
    {
        global $config, $database;
        $postsPerPage = $config->get_int('forumPostsPerPage', 15);
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

        log_info("forum", "Thread {$threadID} created by {$user->name}");

        return $threadID;
    }

    private function save_new_post(int $threadID, User $user): void
    {
        global $config;
        $userID = $user->id;
        $message = $_POST["message"];

        $max_characters = $config->get_int('forumMaxCharsPerPost');
        $message = substr($message, 0, $max_characters);

        global $database;
        $database->execute("
			INSERT INTO forum_posts (thread_id, user_id, date, message)
			VALUES (:thread_id, :user_id, now(), :message)
		", ['thread_id' => $threadID, 'user_id' => $userID, 'message' => $message]);

        $postID = $database->get_last_insert_id("forum_posts_id_seq");

        log_info("forum", "Post {$postID} created by {$user->name}");

        $database->execute("UPDATE forum_threads SET uptodate=now() WHERE id=:id", ['id' => $threadID]);
    }

    private function delete_thread(int $threadID): void
    {
        global $database;
        $database->execute("DELETE FROM forum_threads WHERE id = :id", ['id' => $threadID]);
        $database->execute("DELETE FROM forum_posts WHERE thread_id = :thread_id", ['thread_id' => $threadID]);
    }

    private function delete_post(int $postID): void
    {
        global $database;
        $database->execute("DELETE FROM forum_posts WHERE id = :id", ['id' => $postID]);
    }

    private function threadExists(int $threadID): bool
    {
        global $database;
        $result = $database->get_one("SELECT EXISTS (SELECT * FROM forum_threads WHERE id=:id)", ['id' => $threadID]);
        return $result == 1;
    }
}
