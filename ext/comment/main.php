<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\{Field, Mutation, Type};

use function MicroHTML\{emptyHTML};

/** @phpstan-type DBComment array{id:int,image_id:int,owner_id:int,owner_ip:string,posted:string,comment:string,edited:bool|int} */
#[Type(name: "Comment")]
final class Comment
{
    #[Field]
    public User $owner;
    public function __construct(
        #[Field]
        public int $id,
        public int $image_id,
        public int $owner_id,
        public string $owner_ip,
        #[Field]
        public string $posted,
        #[Field]
        public string $comment,
        public bool $edited,
    ) {
        $this->owner = User::by_id_dangerously_cached($owner_id);
    }

    /** @param DBComment $row */
    public static function from_row(array $row): Comment
    {
        return new Comment(
            $row['id'],
            $row['image_id'],
            $row['owner_id'],
            $row['owner_ip'],
            $row['posted'],
            $row['comment'],
            (bool)$row['edited']
        );
    }

    /** @param DBComment[] $rows
     * @return Comment[] */
    private static function multi_row(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $out[] = self::from_row($r);
        }
        return $out;
    }

    public static function by_id(int $id): Comment
    {
        /** @var ?DBComment */
        $row = Ctx::$database->get_row(
            'SELECT * FROM comments
            WHERE id = :id;',
            ['id' => $id]
        );

        if (is_null($row)) {
            throw new ObjectNotFound("Comment with id $id not found.");
        }
        return self::from_row($row);
    }

    /** @return Comment[] */
    public static function get_all(int $limit, int $offset = 0): array
    {
        /** @var DBComment[] */
        $rows = Ctx::$database->get_all(
            'SELECT * FROM comments
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset',
            ['limit' => $limit, 'offset' => $offset]
        );
        return self::multi_row($rows);
    }

    /** @return Comment[] */
    public static function get_all_from_image(int $image_id): array
    {
        /** @var DBComment[] */
        $rows = Ctx::$database->get_all(
            'SELECT * FROM comments
            WHERE image_id = :image_id
            ORDER BY id DESC',
            ['image_id' => $image_id]
        );
        return self::multi_row($rows);
    }

    /** @return Comment[] */
    public static function get_all_from_user(int $user_id, int $limit, int $offset = 0): array
    {
        /** @var DBComment[] */
        $rows = Ctx::$database->get_all(
            'SELECT * FROM comments
            WHERE owner_id = :owner_id
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset',
            ['owner_id' => $user_id, 'limit' => $limit, 'offset' => $offset]
        );
        return self::multi_row($rows);
    }

    public static function is_dupe(int $image_id, string $comment): bool
    {
        return !\is_null(Ctx::$database->get_one(
            "SELECT id
			FROM comments
			WHERE image_id=:image_id AND comment=:comment",
            ["image_id" => $image_id, "comment" => $comment]
        ));
    }

    public static function count_comments_by_user(User $user): int
    {
        return (int)Ctx::$database->get_one("
			SELECT COUNT(*) AS count
			FROM comments
			WHERE owner_id=:owner_id
		", ["owner_id" => $user->id]);
    }

    /**
     * @return Comment[]
     */
    #[Field(extends: "Post", name: "comments", type: "[Comment!]!")]
    public static function get_comments(Image $post): array
    {
        return self::get_all_from_image($post->id);
    }

    #[Mutation(name: "create_comment")]
    public static function create_comment(int $post_id, string $comment): bool
    {
        send_event(new CheckStringContentEvent($comment));
        send_event(new CommentPostingEvent($post_id, Ctx::$user, $comment));
        return true;
    }
}

/** @extends Extension<CommentListTheme> */
final class CommentList extends Extension
{
    public const KEY = "comment";
    public const VERSION_KEY = "ext_comments_version";

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["comments_locked"] = ImagePropType::BOOL;
    }

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;
        if ($this->get_version() < 5) {
            // shortcut to latest
            if ($this->get_version() < 1) {
                $database->create_table("comments", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					owner_id INTEGER NOT NULL,
					owner_ip SCORE_INET NOT NULL,
					posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					comment TEXT NOT NULL,
                    edited BOOLEAN NOT NULL DEFAULT FALSE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
					FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
				");
                $database->execute("CREATE INDEX comments_image_id_idx ON comments(image_id)", []);
                $database->execute("CREATE INDEX comments_owner_id_idx ON comments(owner_id)", []);
                $database->execute("CREATE INDEX comments_posted_idx ON comments(posted)", []);

                $database->execute("ALTER TABLE images ADD COLUMN comments_locked BOOLEAN NOT NULL DEFAULT FALSE");
                $database->execute("CREATE INDEX images_comments_locked_idx ON images(comments_locked)");
                $this->set_version(5);
            }

            // the whole history
            if ($this->get_version() < 1) {
                $database->create_table("comments", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					owner_id INTEGER NOT NULL,
					owner_ip CHAR(16) NOT NULL,
					posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					comment TEXT NOT NULL
				");
                $database->execute("CREATE INDEX comments_image_id_idx ON comments(image_id)", []);
                $this->set_version(1);
            }

            if ($this->get_version() === 1) {
                $database->execute("CREATE INDEX comments_owner_ip ON comments(owner_ip)");
                $database->execute("CREATE INDEX comments_posted ON comments(posted)");
                $this->set_version(2);
            }

            if ($this->get_version() === 2) {
                $database->execute("ALTER TABLE comments ADD FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE");
                $database->execute("ALTER TABLE comments ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");
                $this->set_version(3);
            }

            if ($this->get_version() === 3) {
                $database->execute("ALTER TABLE images ADD COLUMN comments_locked BOOLEAN NOT NULL DEFAULT FALSE");
                $database->execute("CREATE INDEX images_comments_locked_idx ON images(comments_locked)");
                $this->set_version(4);
            }

            if ($this->get_version() === 4) {
                $database->execute("ALTER TABLE comments ADD COLUMN edited BOOLEAN NOT NULL DEFAULT FALSE");
                $this->set_version(5);
            }
        }
    }

    #[EventListener]
    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('comment/list'), "Comments", category: "comment");
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "comment") {
            $event->add_nav_link(make_link('comment/list'), "All");
            $event->add_nav_link(make_link('ext_doc/comment'), "Help");
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $page = Ctx::$page;
        if ($event->page_matches("comment/add", method: "POST", permission: CommentPermission::CREATE_COMMENT)) {
            $image_id = int_escape($event->POST->req('image_id'));
            send_event(new CheckStringContentEvent($event->POST->req('comment')));
            send_event(new CommentPostingEvent($image_id, Ctx::$user, $event->POST->req('comment')));
            $page->set_redirect(make_link("post/view/$image_id", null, "comment_on_$image_id"));
        } elseif ($event->page_matches("comment/edit", method: "POST", permission: CommentPermission::EDIT_COMMENT)) {
            $comment_id = int_escape($event->POST->req('comment_id'));
            $image_id = int_escape($event->POST->req('image_id'));
            $new_comment = $event->POST->req('comment');
            send_event(new CheckStringContentEvent($new_comment));
            send_event(new CommentEditingEvent($image_id, $comment_id, Ctx::$user, $new_comment));
            $page->set_redirect(make_link("post/view/$image_id", null, "c$comment_id"));
        } elseif ($event->page_matches("comment/delete/{comment_id}/{image_id}", permission: CommentPermission::DELETE_COMMENT)) {
            // FIXME: post, not args
            send_event(new CommentDeletionEvent($event->get_iarg('comment_id')));
            $page->flash("Deleted comment");
            $page->set_redirect(Url::referer_or(make_link("post/view/" . $event->get_iarg('image_id'))));
        } elseif ($event->page_matches("comment/bulk_delete", method: "POST", permission: CommentPermission::DELETE_COMMENT)) {
            $ip = $event->POST->req('ip');

            $comment_ids = $database->get_col("
                SELECT id
                FROM comments
                WHERE owner_ip=:ip
            ", ["ip" => $ip]);
            $num = count($comment_ids);
            Log::warning("comment", "Deleting $num comments from $ip");
            foreach ($comment_ids as $comment_id) {
                send_event(new CommentDeletionEvent($comment_id));
            }
            $page->flash("Deleted $num comments");
            $page->set_redirect(make_link("admin"));
        } elseif ($event->page_matches("comment/list", paged: true)) {
            $threads_per_page = 10;

            $where = Ctx::$config->get(CommentConfig::RECENT_COMMENTS)
                ? "WHERE posted > now() - interval '24 hours'"
                : "";

            $total_pages = cache_get_or_set("comment_pages", fn () => (int)ceil($database->get_one("
                SELECT COUNT(c1)
                FROM (SELECT COUNT(image_id) AS c1 FROM comments $where GROUP BY image_id) AS s1
            ") / $threads_per_page), 600);
            $total_pages = max($total_pages, 1);

            $current_page = $event->get_iarg('page_num', 1) - 1;
            $start = $threads_per_page * $current_page;

            $result = $database->execute("
                SELECT image_id,MAX(posted) AS latest
                FROM comments
                $where
                GROUP BY image_id
                ORDER BY latest DESC
                LIMIT :limit OFFSET :offset
            ", ["limit" => $threads_per_page, "offset" => $start]);

            $user_ratings = RatingsInfo::is_enabled() ? Ratings::get_user_class_privs(Ctx::$user) : [];

            $images = [];
            while ($row = $result->fetch()) {
                $image = Image::by_id((int)$row["image_id"]);
                if (
                    RatingsInfo::is_enabled() && !is_null($image) &&
                    !in_array($image['rating'], $user_ratings)
                ) {
                    $image = null; // this is "clever", I may live to regret it
                }
                if (
                    ApprovalInfo::is_enabled() && !is_null($image) &&
                    $image['approved'] !== true
                ) {
                    $image = null;
                }
                if (!is_null($image)) {
                    $comments = Comment::get_all_from_image($image->id);
                    $images[] = [$image, $comments];
                }
            }

            $this->theme->display_comment_list($images, $current_page + 1, $total_pages, Ctx::$user->can(CommentPermission::CREATE_COMMENT));
        } elseif ($event->page_matches("comment/beta-search/{search}", paged: true)) {
            $search = $event->get_arg('search');
            $page_num = $event->get_iarg('page_num', 1) - 1;
            $duser = User::by_name($search);
            $i_comment_count = Comment::count_comments_by_user($duser);
            $com_per_page = 50;
            $total_pages = (int)ceil($i_comment_count / $com_per_page);
            $comments = Comment::get_all_from_user($duser->id, $com_per_page, $page_num * $com_per_page);
            $this->theme->display_all_user_comments($comments, $page_num + 1, $total_pages, $duser);
        }
    }

    #[EventListener]
    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        // comment lists change all the time, crawlers should
        // index individual image's comments
        $event->add_disallow("comment");
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block();
    }

    #[EventListener]
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        $count = Ctx::$config->get(CommentConfig::COUNT);
        if ($count > 0) {
            $recent = cache_get_or_set("recent_comments", fn () => Comment::get_all($count), 60);
            if (!empty($recent)) {
                $this->theme->display_recent_comments($recent);
            }
        }
    }

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $i_days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;
        $i_comment_count = Comment::count_comments_by_user($event->display_user);
        $h_comment_rate = sprintf("%.1f", ($i_comment_count / $i_days_old));
        $event->add_part(emptyHTML("Comments made: $i_comment_count, $h_comment_rate per day"));

        $recent = Comment::get_all_from_user($event->display_user->id, 10);
        $this->theme->display_recent_user_comments($recent, $event->display_user);
    }

    #[EventListener]
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $comments_locked = (bool)Ctx::$database->get_one(
            "SELECT comments_locked FROM images WHERE id = :id",
            ["id" => $event->image->id]
        );

        $can_post = Ctx::$user->can(CommentPermission::CREATE_COMMENT) &&
                    (!$comments_locked || Ctx::$user->can(CommentPermission::BYPASS_COMMENT_LOCK));

        $comments = Comment::get_all_from_image($event->image->id);
        $this->theme->display_image_comments($event->image, $comments, $can_post, $comments_locked);
    }

    #[EventListener]
    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        if (Ctx::$user->can(CommentPermission::EDIT_COMMENT_LOCK)) {
            $comments_locked = $event->get_param('comments_locked') === "on";
            send_event(new CommentLockSetEvent($event->image->id, $comments_locked));
        }
    }

    #[EventListener]
    public function onCommentLockSet(CommentLockSetEvent $event): void
    {
        if (Ctx::$user->can(CommentPermission::EDIT_COMMENT_LOCK)) {
            Ctx::$database->execute(
                "UPDATE images SET comments_locked = :locked WHERE id = :id",
                ["locked" => $event->locked, "id" => $event->image_id]
            );
        }
    }

    #[EventListener]
    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $comments_locked = (bool)Ctx::$database->get_one(
            "SELECT comments_locked FROM images WHERE id = :id",
            ["id" => $event->image->id]
        );
        $event->add_part($this->theme->get_comments_lock_editor_html($comments_locked), 42);
    }

    #[EventListener]
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->comment_checks($event->user, $event->image_id, $event->comment);
        $event->id = $this->save_new_comment($event->user, $event->image_id, $event->comment);
    }

    #[EventListener]
    public function onCommentEditing(CommentEditingEvent $event): void
    {
        $this->comment_checks($event->user, $event->image_id, $event->comment, $event->comment_id);
        $this->edit_comment($event->user, $event->comment_id, $event->image_id, $event->comment);
    }

    #[EventListener]
    public function onCommentDeletion(CommentDeletionEvent $event): void
    {
        Ctx::$database->execute("
			DELETE FROM comments
			WHERE id=:comment_id
		", ["comment_id" => $event->comment_id]);
        Log::info("comment", "Deleting Comment #{$event->comment_id}");
    }

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches("/^comments(:|<=|<|=|>|>=)(\d+)$/i")) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $comments = $matches[2];
            $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM comments GROUP BY image_id HAVING count(image_id) $cmp $comments)"));
        } elseif ($matches = $event->matches("/^commented_by[=:](.*)$/i")) {
            $user_id = User::name_to_id($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
        } elseif ($matches = $event->matches("/^commented_by_userno[=:]([0-9]+)$/i")) {
            $user_id = int_escape($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
        }
    }

    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Comments", $this->theme->get_help_html());
        }
    }

    private function save_new_comment(User $user, int $image_id, string $comment): int
    {
        Ctx::$database->execute(
            "INSERT INTO comments(image_id, owner_id, owner_ip, posted, comment)
            VALUES(:image_id, :user_id, :remote_addr, now(), :comment)",
            ["image_id" => $image_id, "user_id" => $user->id, "remote_addr" => (string)Network::get_real_ip(), "comment" => $comment]
        );
        $comment_id = Ctx::$database->get_last_insert_id('comments_id_seq');
        $snippet = substr($comment, 0, 100);
        $snippet = str_replace("\n", " ", $snippet);
        $snippet = str_replace("\r", " ", $snippet);
        Log::info("comment", "Comment #$comment_id added to >>$image_id: $snippet");
        return $comment_id;
    }

    private function edit_comment(User $user, int $comment_id, int $image_id, string $comment): void
    {
        Ctx::$database->execute(
            'UPDATE comments
            SET comment = :comment,
            owner_ip = :ip,
            edited = TRUE
            WHERE id = :id
            AND image_id = :image_id',
            ['comment' => $comment, 'ip' => (string)Network::get_real_ip(), 'id' => $comment_id, 'image_id' => $image_id]
        );

        $snippet = substr($comment, 0, 100);
        $snippet = str_replace("\n", " ", $snippet);
        $snippet = str_replace("\r", " ", $snippet);
        Log::info("comment", "Comment $comment_id on >>$image_id edited by $user->name to: $snippet");
    }

    private function is_comment_limit_hit(): bool
    {
        // sqlite fails at intervals
        if (Ctx::$database->get_driver_id() === DatabaseDriverID::SQLITE) {
            return false;
        }

        $window = Ctx::$config->get(CommentConfig::WINDOW);
        $max = Ctx::$config->get(CommentConfig::LIMIT);

        if (Ctx::$database->get_driver_id() === DatabaseDriverID::MYSQL) {
            $window_sql = "interval $window minute";
        } else {
            $window_sql = "interval '$window minute'";
        }

        // window doesn't work as an SQL param because it's inside quotes >_<
        // @phpstan-ignore-next-line
        $result = Ctx::$database->get_all("
			SELECT *
			FROM comments
			WHERE owner_ip = :remote_ip AND posted > now() - $window_sql
		", ["remote_ip" => (string)Network::get_real_ip()]);

        return (count($result) >= $max);
    }

    /**
     * get a hash which semi-uniquely identifies a submission form,
     * to stop spam bots which download the form once then submit
     * many times.
     */
    public static function get_hash(int $offset = 0): string
    {
        return md5((string)Network::get_real_ip() . date("%Y%m%d%H", time() - $offset));
    }

    private static function check_hash(string $hash): bool
    {
        $valid_hashes = [
            self::get_hash(0),
            self::get_hash(3600),
            self::get_hash(7200),
        ];
        return in_array($hash, $valid_hashes);
    }

    private function comment_checks(User $user, int $image_id, string $comment, ?int $comment_id = null): void
    {
        // basic sanity checks
        if (!Ctx::$user->can(CommentPermission::CREATE_COMMENT)) {
            throw new CommentPostingException("You do not have permission to add comments");
        }

        $image = Image::by_id($image_id);
        if (is_null($image)) {
            throw new CommentPostingException("The image does not exist");
        }

        // editing an existing comment
        if (!is_null($comment_id)) {
            $comment_obj = Comment::by_id($comment_id); // will raise an exception if it does not exist
            if ($comment_obj->owner_id !== $user->id) {
                throw new PermissionDenied("You cannot change other users' comments");
            }
        }

        // Check if comments are locked
        $comments_locked = $image["comments_locked"];
        if ($comments_locked && !Ctx::$user->can(CommentPermission::BYPASS_COMMENT_LOCK)) {
            throw new CommentPostingException("Comments are locked on this post");
        }

        if (trim($comment) === "") {
            throw new CommentPostingException("Comments need text...");
        } elseif (strlen($comment) > 9000) {
            throw new CommentPostingException("Comment too long~");
        } elseif (strlen($comment) / strlen(\Safe\gzcompress($comment)) > 10) {
            throw new CommentPostingException("Comment too repetitive~");
        } elseif (!defined("UNITTEST") && !self::check_hash($_POST['hash'])) {
            throw new CommentPostingException(
                "Comment submission form is out of date; refresh the ".
                    "comment form to show you aren't a spammer~"
            );
        }

        // database-querying checks
        if (!$user->can(UserAccountsPermission::BYPASS_CONTENT_CHECKS)) {
            if ($this->is_comment_limit_hit()) {
                throw new CommentPostingException("You've posted several comments recently; wait a minute and try again...");
            } elseif (Comment::is_dupe($image_id, $comment)) {
                throw new CommentPostingException("Someone already made that comment on that image -- try and be more original?");
            }
        }

        // rate-limited external service checks last
        if (!Captcha::check(CommentPermission::SKIP_CAPTCHA)) {
            throw new CommentPostingException("Error in captcha");
        }
    }
}
