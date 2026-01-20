<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\{Field, Mutation, Type};

use function MicroHTML\{emptyHTML};

require_once "vendor/ifixit/php-akismet/akismet.class.php";

final class CommentPostingEvent extends Event
{
    public function __construct(
        public int $image_id,
        public User $user,
        public string $comment
    ) {
        parent::__construct();
    }
}

/**
 * A comment is being deleted. Maybe used by spam
 * detectors to get a feel for what should be deleted
 * and what should be kept?
 */
final class CommentDeletionEvent extends Event
{
    public function __construct(
        public int $comment_id
    ) {
        parent::__construct();
    }
}

final class CommentPostingException extends InvalidInput
{
}

/**
 * Comment lock status is being changed on an image
 */
final class CommentLockSetEvent extends Event
{
    public function __construct(
        public int $image_id,
        public bool $locked
    ) {
        parent::__construct();
    }
}

#[Type(name: "Comment")]
final class Comment
{
    public ?User $owner;
    public int $owner_id;
    public string $owner_name;
    public ?string $owner_email;
    public string $owner_class;
    #[Field]
    public string $comment;
    #[Field]
    public int $comment_id;
    public int $image_id;
    public string $poster_ip;
    #[Field]
    public string $posted;

    /**
     * @param array{
     *     user_id: string|int,
     *     user_name: string,
     *     user_email: ?string,
     *     user_class: string,
     *     comment: string,
     *     comment_id: string|int,
     *     image_id: string|int,
     *     poster_ip: string,
     *     posted: string,
     * } $row
     */
    public function __construct(array $row)
    {
        $this->owner = null;
        $this->owner_id = (int)$row['user_id'];
        $this->owner_name = $row['user_name'];
        $this->owner_email = $row['user_email']; // deprecated
        $this->owner_class = $row['user_class'];
        $this->comment =  $row['comment'];
        $this->comment_id =  (int)$row['comment_id'];
        $this->image_id =  (int)$row['image_id'];
        $this->poster_ip =  $row['poster_ip'];
        $this->posted =  $row['posted'];
    }

    public static function count_comments_by_user(User $user): int
    {
        return (int)Ctx::$database->get_one("
			SELECT COUNT(*) AS count
			FROM comments
			WHERE owner_id=:owner_id
		", ["owner_id" => $user->id]);
    }

    #[Field(name: "owner")]
    public function get_owner(): User
    {
        if (is_null($this->owner)) {
            $this->owner = User::by_id_dangerously_cached($this->owner_id);
        }
        return $this->owner;
    }

    /**
     * @return Comment[]
     */
    #[Field(extends: "Post", name: "comments", type: "[Comment!]!")]
    public static function get_comments(Image $post): array
    {
        return CommentList::get_comments($post->id);
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

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["comments_locked"] = ImagePropType::BOOL;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;
        if ($this->get_version() < 4) {
            // shortcut to latest
            if ($this->get_version() < 1) {
                $database->create_table("comments", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					owner_id INTEGER NOT NULL,
					owner_ip SCORE_INET NOT NULL,
					posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					comment TEXT NOT NULL,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
					FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
				");
                $database->execute("CREATE INDEX comments_image_id_idx ON comments(image_id)", []);
                $database->execute("CREATE INDEX comments_owner_id_idx ON comments(owner_id)", []);
                $database->execute("CREATE INDEX comments_posted_idx ON comments(posted)", []);
                $this->set_version(3);
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
        }
    }

    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link(make_link('comment/list'), "Comments", "comment");
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "comment") {
            $event->add_nav_link(make_link('comment/list'), "All", "list");
            $event->add_nav_link(make_link('ext_doc/comment'), "Help", "help");
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $page = Ctx::$page;
        if ($event->page_matches("comment/add", method: "POST", permission: CommentPermission::CREATE_COMMENT)) {
            $i_iid = int_escape($event->POST->req('image_id'));
            send_event(new CheckStringContentEvent($event->POST->req('comment')));
            send_event(new CommentPostingEvent($i_iid, Ctx::$user, $event->POST->req('comment')));
            $page->set_redirect(make_link("post/view/$i_iid", null, "comment_on_$i_iid"));
        }
        if ($event->page_matches("comment/delete/{comment_id}/{image_id}", permission: CommentPermission::DELETE_COMMENT)) {
            // FIXME: post, not args
            send_event(new CommentDeletionEvent($event->get_iarg('comment_id')));
            $page->flash("Deleted comment");
            $page->set_redirect(Url::referer_or(make_link("post/view/" . $event->get_iarg('image_id'))));
        }
        if ($event->page_matches("comment/bulk_delete", method: "POST", permission: CommentPermission::DELETE_COMMENT)) {
            $ip = $event->POST->req('ip');

            $comment_ids = $database->get_col("
                SELECT id
                FROM comments
                WHERE owner_ip=:ip
            ", ["ip" => $ip]);
            $num = count($comment_ids);
            Log::warning("comment", "Deleting $num comments from $ip");
            foreach ($comment_ids as $cid) {
                send_event(new CommentDeletionEvent($cid));
            }
            $page->flash("Deleted $num comments");
            $page->set_redirect(make_link("admin"));
        }
        if ($event->page_matches("comment/list", paged: true)) {
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
                    $comments = self::get_comments($image->id);
                    $images[] = [$image, $comments];
                }
            }

            $this->theme->display_comment_list($images, $current_page + 1, $total_pages, Ctx::$user->can(CommentPermission::CREATE_COMMENT));
        }
        if ($event->page_matches("comment/beta-search/{search}", paged: true)) {
            $search = $event->get_arg('search');
            $page_num = $event->get_iarg('page_num', 1) - 1;
            $duser = User::by_name($search);
            $i_comment_count = Comment::count_comments_by_user($duser);
            $com_per_page = 50;
            $total_pages = (int)ceil($i_comment_count / $com_per_page);
            $comments = self::get_user_comments($duser->id, $com_per_page, $page_num * $com_per_page);
            $this->theme->display_all_user_comments($comments, $page_num + 1, $total_pages, $duser);
        }
    }

    public function onRobotsBuilding(RobotsBuildingEvent $event): void
    {
        // comment lists change all the time, crawlers should
        // index individual image's comments
        $event->add_disallow("comment");
    }

    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        $this->theme->display_admin_block();
    }

    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        $cc = Ctx::$config->get(CommentConfig::COUNT);
        if ($cc > 0) {
            $recent = cache_get_or_set("recent_comments", fn () => self::get_recent_comments($cc), 60);
            if (count($recent) > 0) {
                $this->theme->display_recent_comments($recent);
            }
        }
    }

    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $i_days_old = ((time() - \Safe\strtotime($event->display_user->join_date)) / 86400) + 1;
        $i_comment_count = Comment::count_comments_by_user($event->display_user);
        $h_comment_rate = sprintf("%.1f", ($i_comment_count / $i_days_old));
        $event->add_part(emptyHTML("Comments made: $i_comment_count, $h_comment_rate per day"));

        $recent = self::get_user_comments($event->display_user->id, 10);
        $this->theme->display_recent_user_comments($recent, $event->display_user);
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $comments_locked = (bool)Ctx::$database->get_one(
            "SELECT comments_locked FROM images WHERE id = :id",
            ["id" => $event->image->id]
        );

        $can_post = Ctx::$user->can(CommentPermission::CREATE_COMMENT) &&
                    (!$comments_locked || Ctx::$user->can(CommentPermission::BYPASS_COMMENT_LOCK));

        $comments = self::get_comments($event->image->id);
        $this->theme->display_image_comments($event->image, $comments, $can_post, $comments_locked);
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        if (Ctx::$user->can(CommentPermission::EDIT_COMMENT_LOCK)) {
            $comments_locked = $event->get_param('comments_locked') === "on";
            send_event(new CommentLockSetEvent($event->image->id, $comments_locked));
        }
    }

    public function onCommentLockSet(CommentLockSetEvent $event): void
    {
        if (Ctx::$user->can(CommentPermission::EDIT_COMMENT_LOCK)) {
            Ctx::$database->execute(
                "UPDATE images SET comments_locked = :locked WHERE id = :id",
                ["locked" => $event->locked, "id" => $event->image_id]
            );
        }
    }

    public function onImageInfoBoxBuilding(ImageInfoBoxBuildingEvent $event): void
    {
        $comments_locked = (bool)Ctx::$database->get_one(
            "SELECT comments_locked FROM images WHERE id = :id",
            ["id" => $event->image->id]
        );
        $event->add_part($this->theme->get_comments_lock_editor_html($comments_locked), 42);
    }

    // TODO: split akismet into a separate class, which can veto the event
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->add_comment_wrapper($event->image_id, $event->user, $event->comment);
    }

    public function onCommentDeletion(CommentDeletionEvent $event): void
    {
        Ctx::$database->execute("
			DELETE FROM comments
			WHERE id=:comment_id
		", ["comment_id" => $event->comment_id]);
        Log::info("comment", "Deleting Comment #{$event->comment_id}");
    }

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

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Comments", $this->theme->get_help_html());
        }
    }

    /**
     * @param literal-string $query
     * @param sql-params-array $args
     * @return Comment[]
     */
    private static function get_generic_comments(string $query, array $args): array
    {
        $rows = Ctx::$database->get_all($query, $args);
        // @phpstan-ignore-next-line
        return array_map(fn (array $row) => new Comment($row), $rows);
    }

    /**
     * @return Comment[]
     */
    private static function get_recent_comments(int $count): array
    {
        return CommentList::get_generic_comments("
			SELECT
				users.id as user_id, users.name as user_name, users.email as user_email, users.class as user_class,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
			FROM comments
			LEFT JOIN users ON comments.owner_id=users.id
			ORDER BY comments.id DESC
			LIMIT :limit
		", ["limit" => $count]);
    }

    /**
     * @return Comment[]
     */
    private static function get_user_comments(int $user_id, int $count, int $offset = 0): array
    {
        return CommentList::get_generic_comments("
			SELECT
				users.id as user_id, users.name as user_name, users.email as user_email, users.class as user_class,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
			FROM comments
			LEFT JOIN users ON comments.owner_id=users.id
			WHERE users.id = :user_id
			ORDER BY comments.id DESC
			LIMIT :limit OFFSET :offset
		", ["user_id" => $user_id, "offset" => $offset, "limit" => $count]);
    }

    /**
     * public just for Image::get_comments()
     * @return Comment[]
     */
    public static function get_comments(int $image_id): array
    {
        return CommentList::get_generic_comments("
			SELECT
				users.id as user_id, users.name as user_name, users.email as user_email, users.class as user_class,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
			FROM comments
			LEFT JOIN users ON comments.owner_id=users.id
			WHERE comments.image_id=:image_id
			ORDER BY comments.id ASC
		", ["image_id" => $image_id]);
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



    private function is_dupe(int $image_id, string $comment): bool
    {
        return (bool)Ctx::$database->get_row("
			SELECT *
			FROM comments
			WHERE image_id=:image_id AND comment=:comment
		", ["image_id" => $image_id, "comment" => $comment]);
    }

    private function add_comment_wrapper(int $image_id, User $user, string $comment): void
    {
        // will raise an exception if anything is wrong
        $this->comment_checks($image_id, $user, $comment);

        // all checks passed
        Ctx::$database->execute(
            "INSERT INTO comments(image_id, owner_id, owner_ip, posted, comment) ".
                "VALUES(:image_id, :user_id, :remote_addr, now(), :comment)",
            ["image_id" => $image_id, "user_id" => $user->id, "remote_addr" => (string)Network::get_real_ip(), "comment" => $comment]
        );
        $cid = Ctx::$database->get_last_insert_id('comments_id_seq');
        $snippet = substr($comment, 0, 100);
        $snippet = str_replace("\n", " ", $snippet);
        $snippet = str_replace("\r", " ", $snippet);
        Log::info("comment", "Comment #$cid added to >>$image_id: $snippet");
    }

    private function comment_checks(int $image_id, User $user, string $comment): void
    {
        // basic sanity checks
        if (!Ctx::$user->can(CommentPermission::CREATE_COMMENT)) {
            throw new CommentPostingException("You do not have permission to add comments");
        }

        $image = Image::by_id($image_id);
        if (is_null($image)) {
            throw new CommentPostingException("The image does not exist");
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
        elseif (!$user->can(UserAccountsPermission::BYPASS_CONTENT_CHECKS) && $this->is_comment_limit_hit()) {
            throw new CommentPostingException("You've posted several comments recently; wait a minute and try again...");
        } elseif (!$user->can(UserAccountsPermission::BYPASS_CONTENT_CHECKS) && $this->is_dupe($image_id, $comment)) {
            throw new CommentPostingException("Someone already made that comment on that image -- try and be more original?");
        }

        // rate-limited external service checks last
        elseif (!Captcha::check(CommentPermission::SKIP_CAPTCHA)) {
            throw new CommentPostingException("Error in captcha");
        }
    }
}
