<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;

/** @phpstan-type Thread array{id:int,user_id:int,title:string,date:string,uptodate:string,response_count:int,sticky:bool|int} */
final class ForumThread
{
    public User $owner;
    public function __construct(
        public int $id,
        public int $owner_id,
        public string $title,
        public string $date,
        public string $update_date,
        public int $response_count,
        public bool $sticky,
    ) {
        $this->owner = User::by_id_dangerously_cached($owner_id);
    }

    /** @param Thread $row */
    public static function from_row(array $row): ForumThread
    {
        return new ForumThread(
            $row['id'],
            $row['user_id'],
            $row['title'],
            $row['date'],
            $row['uptodate'],
            $row['response_count'],
            (bool)$row['sticky']
        );
    }

    public static function by_id(int $id): ForumThread
    {
        /** @var ?Thread */
        $row = Ctx::$database->get_row(
            'SELECT * FROM forum_threads
            WHERE id = :id;',
            ['id' => $id]
        );

        if (is_null($row)) {
            throw new ObjectNotFound("Forum thread with id $id not found.");
        }
        return self::from_row($row);
    }

    /** @return ForumThread[] */
    public static function get_all_threads(int $page_n = 0, int $threads_per_page = 15): array
    {
        /** @var Thread[] */
        $threads = Ctx::$database->get_all(
            'SELECT * FROM forum_threads
            ORDER BY sticky DESC, uptodate DESC
            LIMIT :limit OFFSET :offset',
            ['limit' => $threads_per_page, 'offset' => $page_n * $threads_per_page]
        );
        $out = [];
        foreach ($threads as $t) {
            $out[] = self::from_row($t);
        }
        return $out;
    }

    public static function get_thread_count(): int
    {
        return cache_get_or_set(
            'forum-thread-count',
            fn () => Ctx::$database->get_one('SELECT COUNT(1) FROM forum_threads'),
            600
        );
    }

    public static function get_response_count(int $thread_id): int
    {
        return Ctx::$database->get_one('SELECT response_count FROM forum_threads WHERE id = :id', ['id' => $thread_id]);
    }
}

/** @phpstan-type Post array{id:int,thread_id:int,user_id:int,date:string,message:string} */
final class ForumPost
{
    public User $owner;
    public function __construct(
        public int $id,
        public int $thread_id,
        public int $owner_id,
        public string $date,
        public string $message,
    ) {
        $this->owner = User::by_id_dangerously_cached($owner_id);
    }

    /** @param Post $row */
    public static function from_row(array $row): ForumPost
    {
        return new ForumPost(
            $row['id'],
            $row['thread_id'],
            $row['user_id'],
            $row['date'],
            $row['message']
        );
    }

    /** @return ForumPost[] */
    public static function get_all_posts(int $thread_id, int $page_n = 0, int $posts_per_page = 15): array
    {
        /** @var Post[] */
        $posts = Ctx::$database->get_all(
            'SELECT * FROM forum_posts
            WHERE thread_id = :thread_id
            ORDER BY date ASC
            LIMIT :limit OFFSET :offset',
            ['thread_id' => $thread_id, 'limit' => $posts_per_page, 'offset' => $page_n * $posts_per_page]
        );
        $out = [];
        foreach ($posts as $p) {
            $out[] = self::from_row($p);
        }
        return $out;
    }
}

/*
Todo:
*Quote buttons on posts
*Ability to edit threads/posts?
*/

/** @extends Extension<ForumTheme> */
final class Forum extends Extension
{
    public const KEY = "forum";
    public const VERSION_KEY = "forum_version";

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        if ($this->get_version() < 4) {
            if ($this->get_version() < 1) {
                // shortcut to latest
                Ctx::$database->create_table("forum_threads", "
                        id SCORE_AIPK,
                        user_id INTEGER NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        uptodate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        response_count INTEGER NOT NULL DEFAULT 0,
                        sticky BOOLEAN NOT NULL DEFAULT FALSE,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
                        ");
                Ctx::$database->execute("CREATE INDEX forum_threads_date_idx ON forum_threads(date)", []);

                Ctx::$database->create_table("forum_posts", "
                        id SCORE_AIPK,
                        thread_id INTEGER NOT NULL,
                        user_id INTEGER NOT NULL,
                        date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        message TEXT,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
                        FOREIGN KEY (thread_id) REFERENCES forum_threads (id) ON UPDATE CASCADE ON DELETE CASCADE
                        ");
                Ctx::$database->execute("CREATE INDEX forum_posts_date_idx ON forum_posts(date)", []);

                $this->set_version(4);
            }
            if ($this->get_version() < 2) {
                Ctx::$database->execute("ALTER TABLE forum_threads ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
                Ctx::$database->execute("ALTER TABLE forum_posts ADD FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT");
                $this->set_version(2);
            }
            if ($this->get_version() < 3) {
                Ctx::$database->standardise_boolean("forum_threads", "sticky");
                $this->set_version(3);
            }

            if ($this->get_version() < 4) {
                Ctx::$database->execute("ALTER TABLE forum_threads ADD response_count INTEGER NOT NULL DEFAULT 0");
                Ctx::$database->execute("UPDATE forum_threads ft SET response_count = (SELECT COUNT(1) FROM forum_posts fp WHERE fp.thread_id = ft.id)");
                $this->set_version(4);
            }
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $threads_count = Ctx::$database->get_one("SELECT COUNT(*) FROM forum_threads WHERE user_id=:user_id", ['user_id' => $event->display_user->id]);
        $posts_count = Ctx::$database->get_one("SELECT COUNT(*) FROM forum_posts WHERE user_id=:user_id", ['user_id' => $event->display_user->id]);

        $days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;

        $threads_rate = sprintf("%.1f", ($threads_count / $days_old));
        $posts_rate = sprintf("%.1f", ($posts_count / $days_old));

        $event->add_part(emptyHTML("Forum threads: $threads_count, $threads_rate per day"));
        $event->add_part(emptyHTML("Forum posts: $posts_count, $posts_rate per day"));
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('forum/index'), "Forum", "forum");
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        $user = Ctx::$user;
        $page = Ctx::$page;
        if ($event->page_matches("forum/index", method: "GET", paged: true)) {
            $page_number = $event->get_iarg('page_num', 1) - 1;
            $this->show_last_threads($page_number, $user->can(ForumPermission::FORUM_ADMIN));
            if ($user->can(ForumPermission::FORUM_CREATE)) {
                $this->theme->display_new_thread_composer();
            }
        } elseif ($event->page_matches("forum/view/{thread_id}", method: "GET", paged: true)) {
            $thread_id = $event->get_iarg('thread_id');
            $page_number = $event->get_iarg('page_num', 1) - 1;

            $this->show_posts($thread_id, $page_number, $user->can(ForumPermission::FORUM_ADMIN));
            if ($user->can(ForumPermission::FORUM_ADMIN)) {
                $this->theme->add_actions_block($thread_id);
            }
            if ($user->can(ForumPermission::FORUM_CREATE)) {
                $this->theme->display_new_post_composer($thread_id);
            }
        } elseif ($event->page_matches("forum/new", method: "GET", permission: ForumPermission::FORUM_CREATE)) {
            $this->theme->display_new_thread_composer();
        } elseif ($event->page_matches("forum/create", method: "POST", permission: ForumPermission::FORUM_CREATE)) {
            $title = $event->POST->req('title');
            $sticky = $event->POST->offsetExists('sticky');
            $message = $event->POST->req('message');
            send_event(new CheckStringContentEvent($title));
            $ftpe = send_event(new ForumThreadPostingEvent(Ctx::$user, $title, $sticky));
            send_event(new CheckStringContentEvent($message));
            send_event(new ForumPostPostingEvent(Ctx::$user, $ftpe->id, $message));
            $page->set_redirect(make_link("forum/view/$ftpe->id/1"));
        } elseif ($event->page_matches("forum/answer", method: "POST", permission: ForumPermission::FORUM_CREATE)) {
            $thread_id = int_escape($event->POST->req('thread_id'));
            $message = $event->POST->req('message');
            send_event(new CheckStringContentEvent($message));
            send_event(new ForumPostPostingEvent(Ctx::$user, $thread_id, $message));
            $total_pages = $this->get_total_pages_for_thread($thread_id);
            $page->set_redirect(make_link("forum/view/$thread_id/$total_pages"));
        } elseif ($event->page_matches("forum/delete/{thread_id}/{post_id}", method: "POST", permission: ForumPermission::FORUM_ADMIN)) {
            $thread_id = $event->get_iarg('thread_id');
            $post_id = $event->get_iarg('post_id');
            send_event(new ForumPostDeletionEvent($thread_id, $post_id));
            $page->set_redirect(make_link("forum/view/$thread_id"));
        } elseif ($event->page_matches("forum/nuke/{thread_id}", method: "POST", permission: ForumPermission::FORUM_ADMIN)) {
            $thread_id = $event->get_iarg('thread_id');
            send_event(new ForumThreadDeletionEvent($thread_id));
            $page->set_redirect(make_link("forum/index"));
        }
    }

    public function onForumThreadPosting(ForumThreadPostingEvent $event): void
    {
        $this->forum_checks($event->user, $event->title);
        $event->id = $this->save_new_thread($event->user, $event->title, $event->sticky);
    }
    public function onForumPostPosting(ForumPostPostingEvent $event): void
    {
        $this->forum_checks($event->user, $event->message, $event->thread_id);
        $this->save_new_post($event->user, $event->thread_id, $event->message);
    }

    public function onForumThreadDeletion(ForumThreadDeletionEvent $event): void
    {
        $this->delete_thread($event->thread_id);
    }

    public function onForumPostDeletion(ForumPostDeletionEvent $event): void
    {
        $this->delete_post($event->thread_id, $event->post_id);
    }

    private function forum_checks(User $user, string $content, ?int $thread_id = null): void
    {
        // basic sanity checks
        if (!$user->can(ForumPermission::FORUM_CREATE)) {
            throw new ForumPostingException("You do not have permission to create forum threads or posts");
        }

        $kind = "Title";
        if (!is_null($thread_id)) {
            ForumThread::by_id($thread_id); // will raise an exception if it does not exist
            $kind = "Message";  // thread_id is null on creating a new thread, hence the check is for a title, not message
        }

        if (trim($content) === "") {
            throw new ForumPostingException("$kind needs text...");
        } elseif (strlen($content) > (is_null($thread_id) ? Ctx::$config->get(ForumConfig::TITLE_SUBSTRING) : Ctx::$config->get(ForumConfig::MAX_CHARS_PER_POST))) {
            throw new ForumPostingException("$kind too long~");
        }
    }

    private function get_total_pages_for_thread(int $thread_id): int
    {
        return (int) ceil(ForumThread::get_response_count($thread_id) / Ctx::$config->get(ForumConfig::POSTS_PER_PAGE));
    }

    private function show_last_threads(int $page_number, bool $showAdminOptions = false): void
    {
        $threads_per_page = Ctx::$config->get(ForumConfig::THREADS_PER_PAGE);

        $threads = ForumThread::get_all_threads($page_number, $threads_per_page);
        $totalPages = (int)ceil(ForumThread::get_thread_count() / $threads_per_page);
        $this->theme->display_thread_list($threads, $showAdminOptions, $page_number + 1, $totalPages);
    }

    private function show_posts(int $thread_id, int $page_number, bool $showAdminOptions = false): void
    {
        $posts_per_page = Ctx::$config->get(ForumConfig::POSTS_PER_PAGE);
        $thread = ForumThread::by_id($thread_id);
        $posts = ForumPost::get_all_posts($thread_id, $page_number, $posts_per_page);
        $totalPages = (int)ceil($thread->response_count / $posts_per_page);

        $this->theme->display_thread($thread, $posts, $showAdminOptions, $thread_id, $page_number + 1, $totalPages);
    }

    private function save_new_thread(User $user, string $title, bool $sticky): int
    {
        $title = substr($title, 0, Ctx::$config->get(ForumConfig::TITLE_SUBSTRING));
        Ctx::$database->execute(
            "INSERT INTO forum_threads
			(title, sticky, user_id, date, uptodate)
			VALUES
			(:title, :sticky, :user_id, now(), now())",
            ['title' => $title, 'sticky' => $sticky, 'user_id' => $user->id]
        );

        $thread_id = Ctx::$database->get_last_insert_id('forum_threads_id_seq');

        Log::info('forum', "Thread $thread_id created by $user->name");
        Ctx::$cache->delete('forum-thread-count');
        return $thread_id;
    }

    private function save_new_post(User $user, int $thread_id, string $message): void
    {
        $message = substr($message, 0, Ctx::$config->get(ForumConfig::MAX_CHARS_PER_POST));

        Ctx::$database->execute(
            'INSERT INTO forum_posts (thread_id, user_id, date, message)
			VALUES (:thread_id, :user_id, now(), :message)',
            ['thread_id' => $thread_id, 'user_id' => $user->id, 'message' => $message]
        );

        $post_id = Ctx::$database->get_last_insert_id('forum_posts_id_seq');

        Log::info('forum', "Post $post_id created by $user->name");

        Ctx::$database->execute('UPDATE forum_threads SET uptodate = now(), response_count = response_count + 1 WHERE id = :id', ['id' => $thread_id]);
    }

    private function delete_thread(int $thread_id): void
    {
        Ctx::$database->execute('DELETE FROM forum_threads WHERE id = :id', ['id' => $thread_id]);
        Ctx::$database->execute('DELETE FROM forum_posts WHERE thread_id = :thread_id', ['thread_id' => $thread_id]);
        Ctx::$cache->delete('forum-thread-count');
    }

    private function delete_post(int $thread_id, int $post_id): void
    {
        Ctx::$database->execute('DELETE FROM forum_posts WHERE id = :id', ['id' => $post_id]);
        Ctx::$database->execute('UPDATE forum_threads SET response_count = response_count - 1 WHERE id = :id', ['id' => $thread_id]);
    }
}
