<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;
use GQLA\Query;
use GQLA\Mutation;

require_once "vendor/ifixit/php-akismet/akismet.class.php";

class CommentPostingEvent extends Event
{
    public int $image_id;
    public User $user;
    public string $comment;

    public function __construct(int $image_id, User $user, string $comment)
    {
        parent::__construct();
        $this->image_id = $image_id;
        $this->user = $user;
        $this->comment = $comment;
    }
}

/**
 * A comment is being deleted. Maybe used by spam
 * detectors to get a feel for what should be deleted
 * and what should be kept?
 */
class CommentDeletionEvent extends Event
{
    public int $comment_id;

    public function __construct(int $comment_id)
    {
        parent::__construct();
        $this->comment_id = $comment_id;
    }
}

class CommentPostingException extends InvalidInput
{
}

#[Type(name: "Comment")]
class Comment
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
     * @param array<string,mixed> $row
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
        global $database;
        return (int)$database->get_one("
			SELECT COUNT(*) AS count
			FROM comments
			WHERE owner_id=:owner_id
		", ["owner_id" => $user->id]);
    }

    #[Field(name: "owner")]
    public function get_owner(): User
    {
        if (empty($this->owner)) {
            $this->owner = User::by_id($this->owner_id);
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
        global $user;
        send_event(new CommentPostingEvent($post_id, $user, $comment));
        return true;
    }
}

class CommentList extends Extension
{
    /** @var CommentListTheme $theme */
    public Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        global $config;
        $config->set_default_int('comment_window', 5);
        $config->set_default_int('comment_limit', 10);
        $config->set_default_int('comment_list_count', 10);
        $config->set_default_int('comment_count', 5);
        $config->set_default_bool('comment_captcha', false);
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;
        if ($this->get_version("ext_comments_version") < 3) {
            // shortcut to latest
            if ($this->get_version("ext_comments_version") < 1) {
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
                $this->set_version("ext_comments_version", 3);
            }

            // the whole history
            if ($this->get_version("ext_comments_version") < 1) {
                $database->create_table("comments", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					owner_id INTEGER NOT NULL,
					owner_ip CHAR(16) NOT NULL,
					posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					comment TEXT NOT NULL
				");
                $database->execute("CREATE INDEX comments_image_id_idx ON comments(image_id)", []);
                $this->set_version("ext_comments_version", 1);
            }

            if ($this->get_version("ext_comments_version") == 1) {
                $database->execute("CREATE INDEX comments_owner_ip ON comments(owner_ip)");
                $database->execute("CREATE INDEX comments_posted ON comments(posted)");
                $this->set_version("ext_comments_version", 2);
            }

            if ($this->get_version("ext_comments_version") == 2) {
                $this->set_version("ext_comments_version", 3);
                $database->execute("ALTER TABLE comments ADD FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE");
                $database->execute("ALTER TABLE comments ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");
            }

            // FIXME: add foreign keys, bump to v3
        }
    }


    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        $event->add_nav_link("comment", new Link('comment/list'), "Comments");
    }


    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "comment") {
            $event->add_nav_link("comment_list", new Link('comment/list'), "All");
            $event->add_nav_link("comment_help", new Link('ext_doc/comment'), "Help");
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $cache, $config, $database, $user, $page;
        if ($event->page_matches("comment/add", method: "POST", permission: Permissions::CREATE_COMMENT)) {
            $i_iid = int_escape($event->req_POST('image_id'));
            send_event(new CommentPostingEvent($i_iid, $user, $event->req_POST('comment')));
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$i_iid", null, "comment_on_$i_iid"));
        }
        if ($event->page_matches("comment/delete/{comment_id}/{image_id}", permission: Permissions::DELETE_COMMENT)) {
            // FIXME: post, not args
            send_event(new CommentDeletionEvent($event->get_iarg('comment_id')));
            $page->flash("Deleted comment");
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(referer_or(make_link("post/view/" . $event->get_iarg('image_id'))));
        }
        if ($event->page_matches("comment/bulk_delete", method: "POST", permission: Permissions::DELETE_COMMENT)) {
            $ip = $event->req_POST('ip');

            $comment_ids = $database->get_col("
                SELECT id
                FROM comments
                WHERE owner_ip=:ip
            ", ["ip" => $ip]);
            $num = count($comment_ids);
            log_warning("comment", "Deleting $num comments from $ip");
            foreach ($comment_ids as $cid) {
                send_event(new CommentDeletionEvent($cid));
            }
            $page->flash("Deleted $num comments");

            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("admin"));
        }
        if ($event->page_matches("comment/list", paged: true)) {
            $threads_per_page = 10;

            $where = SPEED_HAX ? "WHERE posted > now() - interval '24 hours'" : "";

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

            $user_ratings = Extension::is_enabled(RatingsInfo::KEY) ? Ratings::get_user_class_privs($user) : [];

            $images = [];
            while ($row = $result->fetch()) {
                $image = Image::by_id((int)$row["image_id"]);
                if (
                    Extension::is_enabled(RatingsInfo::KEY) && !is_null($image) &&
                    !in_array($image['rating'], $user_ratings)
                ) {
                    $image = null; // this is "clever", I may live to regret it
                }
                if (
                    Extension::is_enabled(ApprovalInfo::KEY) && !is_null($image) &&
                    $config->get_bool(ApprovalConfig::IMAGES) &&
                    $image['approved'] !== true
                ) {
                    $image = null;
                }
                if (!is_null($image)) {
                    $comments = $this->get_comments($image->id);
                    $images[] = [$image, $comments];
                }
            }

            $this->theme->display_comment_list($images, $current_page + 1, $total_pages, $user->can(Permissions::CREATE_COMMENT));
        }
        if ($event->page_matches("comment/beta-search/{search}", paged: true)) {
            $search = $event->get_arg('search');
            $page_num = $event->get_iarg('page_num', 1) - 1;
            $duser = User::by_name($search);
            $i_comment_count = Comment::count_comments_by_user($duser);
            $com_per_page = 50;
            $total_pages = (int)ceil($i_comment_count / $com_per_page);
            $comments = $this->get_user_comments($duser->id, $com_per_page, $page_num * $com_per_page);
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
        global $cache, $config;
        $cc = $config->get_int("comment_count");
        if ($cc > 0) {
            $recent = cache_get_or_set("recent_comments", fn () => $this->get_recent_comments($cc), 60);
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
        $event->add_part("Comments made: $i_comment_count, $h_comment_rate per day");

        $recent = $this->get_user_comments($event->display_user->id, 10);
        $this->theme->display_recent_user_comments($recent, $event->display_user);
    }

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $user;
        $this->theme->display_image_comments(
            $event->image,
            $this->get_comments($event->image->id),
            $user->can(Permissions::CREATE_COMMENT)
        );
    }

    // TODO: split akismet into a separate class, which can veto the event
    public function onCommentPosting(CommentPostingEvent $event): void
    {
        $this->add_comment_wrapper($event->image_id, $event->user, $event->comment);
    }

    public function onCommentDeletion(CommentDeletionEvent $event): void
    {
        global $database;
        $database->execute("
			DELETE FROM comments
			WHERE id=:comment_id
		", ["comment_id" => $event->comment_id]);
        log_info("comment", "Deleting Comment #{$event->comment_id}");
    }

    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $sb = $event->panel->create_new_block("Comment Options");
        $sb->add_bool_option("comment_captcha", "Require CAPTCHA for anonymous comments: ");
        $sb->add_label("<br>Limit to ");
        $sb->add_int_option("comment_limit");
        $sb->add_label(" comments per ");
        $sb->add_int_option("comment_window");
        $sb->add_label(" minutes");
        $sb->add_label("<br>Show ");
        $sb->add_int_option("comment_count");
        $sb->add_label(" recent comments on the index");
        $sb->add_label("<br>Show ");
        $sb->add_int_option("comment_list_count");
        $sb->add_label(" comments per image on the list");
        $sb->add_label("<br>Make samefags public ");
        $sb->add_bool_option("comment_samefags_public");
    }

    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^comments([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $comments = $matches[2];
            $event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM comments GROUP BY image_id HAVING count(image_id) $cmp $comments)"));
        } elseif (preg_match("/^commented_by[=|:](.*)$/i", $event->term, $matches)) {
            $user_id = User::name_to_id($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
        } elseif (preg_match("/^commented_by_userno[=|:]([0-9]+)$/i", $event->term, $matches)) {
            $user_id = int_escape($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Comments";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }

    /**
     * @param array<string,mixed> $args
     * @return Comment[]
     */
    private static function get_generic_comments(string $query, array $args): array
    {
        global $database;
        $rows = $database->get_all($query, $args);
        $comments = [];
        foreach ($rows as $row) {
            $comments[] = new Comment($row);
        }
        return $comments;
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
        global $config, $database;

        // sqlite fails at intervals
        if ($database->get_driver_id() === DatabaseDriverID::SQLITE) {
            return false;
        }

        $window = $config->get_int('comment_window');
        $max = $config->get_int('comment_limit');

        if ($database->get_driver_id() == DatabaseDriverID::MYSQL) {
            $window_sql = "interval $window minute";
        } else {
            $window_sql = "interval '$window minute'";
        }

        // window doesn't work as an SQL param because it's inside quotes >_<
        $result = $database->get_all("
			SELECT *
			FROM comments
			WHERE owner_ip = :remote_ip AND posted > now() - $window_sql
		", ["remote_ip" => get_real_ip()]);

        return (count($result) >= $max);
    }

    /**
     * get a hash which semi-uniquely identifies a submission form,
     * to stop spam bots which download the form once then submit
     * many times.
     *
     * FIXME: assumes comments are posted via HTTP...
     */
    public static function get_hash(): string
    {
        return md5(get_real_ip() . date("%Y%m%d"));
    }

    private function is_spam_akismet(string $text): bool
    {
        global $config, $user;
        $key = $config->get_string('comment_wordpress_key');
        if (!is_null($key) && strlen($key) > 0) {
            $comment = [
                'author'       => $user->name,
                'email'        => $user->email,
                'website'      => '',
                'body'         => $text,
                'permalink'    => '',
                'referrer'     => $_SERVER['HTTP_REFERER'] ?? 'none',
                'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? 'none',
            ];

            // @phpstan-ignore-next-line
            $akismet = new \Akismet($_SERVER['SERVER_NAME'], $key, $comment);

            // @phpstan-ignore-next-line
            if ($akismet->errorsExist()) {
                return false;
            } else {
                // @phpstan-ignore-next-line
                return $akismet->isSpam();
            }
        }

        return false;
    }

    private function is_dupe(int $image_id, string $comment): bool
    {
        global $database;
        return (bool)$database->get_row("
			SELECT *
			FROM comments
			WHERE image_id=:image_id AND comment=:comment
		", ["image_id" => $image_id, "comment" => $comment]);
    }
    // do some checks

    private function add_comment_wrapper(int $image_id, User $user, string $comment): void
    {
        global $database, $page;

        if (!$user->can(Permissions::BYPASS_COMMENT_CHECKS)) {
            // will raise an exception if anything is wrong
            $this->comment_checks($image_id, $user, $comment);
        }

        // all checks passed
        if ($user->is_anonymous()) {
            $page->add_cookie("nocache", "Anonymous Commenter", time() + 60 * 60 * 24, "/");
        }
        $database->execute(
            "INSERT INTO comments(image_id, owner_id, owner_ip, posted, comment) ".
                "VALUES(:image_id, :user_id, :remote_addr, now(), :comment)",
            ["image_id" => $image_id, "user_id" => $user->id, "remote_addr" => get_real_ip(), "comment" => $comment]
        );
        $cid = $database->get_last_insert_id('comments_id_seq');
        $snippet = substr($comment, 0, 100);
        $snippet = str_replace("\n", " ", $snippet);
        $snippet = str_replace("\r", " ", $snippet);
        log_info("comment", "Comment #$cid added to >>$image_id: $snippet");
    }

    private function comment_checks(int $image_id, User $user, string $comment): void
    {
        global $config, $page;

        // basic sanity checks
        if (!$user->can(Permissions::CREATE_COMMENT)) {
            throw new CommentPostingException("Anonymous posting has been disabled");
        } elseif (is_null(Image::by_id($image_id))) {
            throw new CommentPostingException("The image does not exist");
        } elseif (trim($comment) == "") {
            throw new CommentPostingException("Comments need text...");
        } elseif (strlen($comment) > 9000) {
            throw new CommentPostingException("Comment too long~");
        }

        // advanced sanity checks
        elseif (strlen($comment) / strlen(\Safe\gzcompress($comment)) > 10) {
            throw new CommentPostingException("Comment too repetitive~");
        } elseif ($user->is_anonymous() && ($_POST['hash'] != $this->get_hash())) {
            $page->add_cookie("nocache", "Anonymous Commenter", time() + 60 * 60 * 24, "/");
            throw new CommentPostingException(
                "Comment submission form is out of date; refresh the ".
                    "comment form to show you aren't a spammer~"
            );
        }

        // database-querying checks
        elseif ($this->is_comment_limit_hit()) {
            throw new CommentPostingException("You've posted several comments recently; wait a minute and try again...");
        } elseif ($this->is_dupe($image_id, $comment)) {
            throw new CommentPostingException("Someone already made that comment on that image -- try and be more original?");
        }

        // rate-limited external service checks last
        elseif ($config->get_bool('comment_captcha') && !captcha_check()) {
            throw new CommentPostingException("Error in captcha");
        } elseif ($user->is_anonymous() && $this->is_spam_akismet($comment)) {
            throw new CommentPostingException("Akismet thinks that your comment is spam. Try rewriting the comment, or logging in.");
        }
    }
}
