<?php
/**
 * Name: Image Comments
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to make comments on images
 * Documentation:
 *  Formatting is done with the standard formatting API (normally BBCode)
 */

require_once "lib/akismet.class.php";

class CommentPostingEvent extends Event {
	/** @var  int */
	public $image_id;
	/** @var \User */
	public $user;
	/** @var string  */
	public $comment;

	/**
	 * @param int $image_id
	 * @param \User $user
	 * @param string $comment
	 */
	public function __construct($image_id, $user, $comment) {
		assert('is_numeric($image_id)');
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
class CommentDeletionEvent extends Event {
	/** @var  int */
	public $comment_id;

	/**
	 * @param int $comment_id
	 */
	public function __construct($comment_id) {
		assert('is_numeric($comment_id)');
		$this->comment_id = $comment_id;
	}
}

class CommentPostingException extends SCoreException {}

class Comment {
	var $owner, $owner_id, $owner_name, $owner_email, $owner_class;
	var $comment, $comment_id;
	var $image_id, $poster_ip, $posted;

	public function __construct($row) {
		$this->owner = null;
		$this->owner_id = $row['user_id'];
		$this->owner_name = $row['user_name'];
		$this->owner_email = $row['user_email']; // deprecated
		$this->owner_class = $row['user_class'];
		$this->comment =  $row['comment'];
		$this->comment_id =  $row['comment_id'];
		$this->image_id =  $row['image_id'];
		$this->poster_ip =  $row['poster_ip'];
		$this->posted =  $row['posted'];
	}

	/**
	 * @param User $user
	 * @return mixed
	 */
	public static function count_comments_by_user($user) {
		global $database;
		return $database->get_one("
			SELECT COUNT(*) AS count
			FROM comments
			WHERE owner_id=:owner_id
		", array("owner_id"=>$user->id));
	}

	/**
	 * @return null|User
	 */
	public function get_owner() {
		if(empty($this->owner)) $this->owner = User::by_id($this->owner_id);
		return $this->owner;
	}
}

class CommentList extends Extension {
	/** @var CommentListTheme $theme */
	var $theme;

	public function onInitExt(InitExtEvent $event) {
		global $config, $database;
		$config->set_default_int('comment_window', 5);
		$config->set_default_int('comment_limit', 10);
		$config->set_default_int('comment_list_count', 10);
		$config->set_default_int('comment_count', 5);
		$config->set_default_bool('comment_captcha', false);

		if($config->get_int("ext_comments_version") < 3) {
			// shortcut to latest
			if($config->get_int("ext_comments_version") < 1) {
				$database->create_table("comments", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					owner_id INTEGER NOT NULL,
					owner_ip SCORE_INET NOT NULL,
					posted SCORE_DATETIME DEFAULT NULL,
					comment TEXT NOT NULL,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
					FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
				");
				$database->execute("CREATE INDEX comments_image_id_idx ON comments(image_id)", array());
				$database->execute("CREATE INDEX comments_owner_id_idx ON comments(owner_id)", array());
				$database->execute("CREATE INDEX comments_posted_idx ON comments(posted)", array());
				$config->set_int("ext_comments_version", 3);
			}

			// the whole history
			if($config->get_int("ext_comments_version") < 1) {
				$database->create_table("comments", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					owner_id INTEGER NOT NULL,
					owner_ip CHAR(16) NOT NULL,
					posted SCORE_DATETIME DEFAULT NULL,
					comment TEXT NOT NULL
				");
				$database->execute("CREATE INDEX comments_image_id_idx ON comments(image_id)", array());
				$config->set_int("ext_comments_version", 1);
			}

			if($config->get_int("ext_comments_version") == 1) {
				$database->Execute("CREATE INDEX comments_owner_ip ON comments(owner_ip)");
				$database->Execute("CREATE INDEX comments_posted ON comments(posted)");
				$config->set_int("ext_comments_version", 2);
			}

			if($config->get_int("ext_comments_version") == 2) {
				$config->set_int("ext_comments_version", 3);
				$database->Execute("ALTER TABLE comments ADD FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE");
				$database->Execute("ALTER TABLE comments ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT");
			}

			// FIXME: add foreign keys, bump to v3
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("comment")) {
			switch($event->get_arg(0)) {
				case "add": $this->onPageRequest_add(); break;
				case "delete": $this->onPageRequest_delete($event); break;
				case "bulk_delete": $this->onPageRequest_bulk_delete(); break;
				case "list": $this->onPageRequest_list($event); break;
				case "beta-search": $this->onPageRequest_beta_search($event); break;
			}
		}
	}

	private function onPageRequest_add() {
		global $user, $page;
		if (isset($_POST['image_id']) && isset($_POST['comment'])) {
			try {
				$i_iid = int_escape($_POST['image_id']);
				$cpe = new CommentPostingEvent($_POST['image_id'], $user, $_POST['comment']);
				send_event($cpe);
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/view/$i_iid#comment_on_$i_iid"));
			} catch (CommentPostingException $ex) {
				$this->theme->display_error(403, "Comment Blocked", $ex->getMessage());
			}
		}
	}

	private function onPageRequest_delete(PageRequestEvent $event) {
		global $user, $page;
		if ($user->can("delete_comment")) {
			// FIXME: post, not args
			if ($event->count_args() === 3) {
				send_event(new CommentDeletionEvent($event->get_arg(1)));
				flash_message("Deleted comment");
				$page->set_mode("redirect");
				if (!empty($_SERVER['HTTP_REFERER'])) {
					$page->set_redirect($_SERVER['HTTP_REFERER']);
				} else {
					$page->set_redirect(make_link("post/view/" . $event->get_arg(2)));
				}
			}
		} else {
			$this->theme->display_permission_denied();
		}
	}

	private function onPageRequest_bulk_delete() {
		global $user, $database, $page;
		if ($user->can("delete_comment") && !empty($_POST["ip"])) {
			$ip = $_POST['ip'];

			$comment_ids = $database->get_col("
				SELECT id
				FROM comments
				WHERE owner_ip=:ip
			", array("ip" => $ip));
			$num = count($comment_ids);
			log_warning("comment", "Deleting $num comments from $ip");
			foreach($comment_ids as $cid) {
				send_event(new CommentDeletionEvent($cid));
			}
			flash_message("Deleted $num comments");

			$page->set_mode("redirect");
			$page->set_redirect(make_link("admin"));
		} else {
			$this->theme->display_permission_denied();
		}
	}

	private function onPageRequest_list(PageRequestEvent $event) {
		$page_num = int_escape($event->get_arg(1));
		$this->build_page($page_num);
	}

	private function onPageRequest_beta_search(PageRequestEvent $event) {
		$search = $event->get_arg(1);
		$page_num = int_escape($event->get_arg(2));
		$duser = User::by_name($search);
		$i_comment_count = Comment::count_comments_by_user($duser);
		$com_per_page = 50;
		$total_pages = ceil($i_comment_count / $com_per_page);
		$page_num = clamp($page_num, 1, $total_pages);
		$comments = $this->get_user_comments($duser->id, $com_per_page, ($page_num - 1) * $com_per_page);
		$this->theme->display_all_user_comments($comments, $page_num, $total_pages, $duser);
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_admin_block();
	}

	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $database;
		$cc = $config->get_int("comment_count");
		if($cc > 0) {
			$recent = $database->cache->get("recent_comments");
			if(empty($recent)) {
				$recent = $this->get_recent_comments($cc);
				$database->cache->set("recent_comments", $recent, 60);
			}
			if(count($recent) > 0) {
				$this->theme->display_recent_comments($recent);
			}
		}
	}

	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		$i_days_old = ((time() - strtotime($event->display_user->join_date)) / 86400) + 1;
		$i_comment_count = Comment::count_comments_by_user($event->display_user);
		$h_comment_rate = sprintf("%.1f", ($i_comment_count / $i_days_old));
		$event->add_stats("Comments made: $i_comment_count, $h_comment_rate per day");

		$recent = $this->get_user_comments($event->display_user->id, 10);
		$this->theme->display_recent_user_comments($recent, $event->display_user);
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $user;
		$this->theme->display_image_comments(
			$event->image,
			$this->get_comments($event->image->id),
			$user->can("create_comment")
		);
	}

	// TODO: split akismet into a separate class, which can veto the event
	public function onCommentPosting(CommentPostingEvent $event) {
		$this->add_comment_wrapper($event->image_id, $event->user, $event->comment);
	}

	public function onCommentDeletion(CommentDeletionEvent $event) {
		global $database;
		$database->Execute("
			DELETE FROM comments
			WHERE id=:comment_id
		", array("comment_id"=>$event->comment_id));
		log_info("comment", "Deleting Comment #{$event->comment_id}");
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Comment Options");
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
		$event->panel->add_block($sb);
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		$matches = array();

		if(preg_match("/^comments([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)$/i", $event->term, $matches)) {
			$cmp = ltrim($matches[1], ":") ?: "=";
			$comments = $matches[2];
			$event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM comments GROUP BY image_id HAVING count(image_id) $cmp $comments)"));
		}
		else if(preg_match("/^commented_by[=|:](.*)$/i", $event->term, $matches)) {
			$user = User::by_name($matches[1]);
			if(!is_null($user)) {
				$user_id = $user->id;
			} else {
				$user_id = -1;
			}

			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
		}
		else if(preg_match("/^commented_by_userno[=|:]([0-9]+)$/i", $event->term, $matches)) {
			$user_id = int_escape($matches[1]);
			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
		}
	}

// page building {{{
	/**
	 * @param int $current_page
	 */
	private function build_page(/*int*/ $current_page) {
		global $database, $user;

		$where = SPEED_HAX ? "WHERE posted > now() - interval '24 hours'" : "";
		
		$total_pages = $database->cache->get("comment_pages");
		if(empty($total_pages)) {
			$total_pages = (int)($database->get_one("
				SELECT COUNT(c1)
				FROM (SELECT COUNT(image_id) AS c1 FROM comments $where GROUP BY image_id) AS s1
			") / 10);
			$database->cache->set("comment_pages", $total_pages, 600);
		}
		$total_pages = max($total_pages, 1);

		$current_page = clamp($current_page, 1, $total_pages);
		
		$threads_per_page = 10;
		$start = $threads_per_page * ($current_page - 1);

		$result = $database->Execute("
			SELECT image_id,MAX(posted) AS latest
			FROM comments
			$where
			GROUP BY image_id
			ORDER BY latest DESC
			LIMIT :limit OFFSET :offset
		", array("limit"=>$threads_per_page, "offset"=>$start));

		$user_ratings = ext_is_live("Ratings") ? Ratings::get_user_privs($user) : "";

		$images = array();
		while($row = $result->fetch()) {
			$image = Image::by_id($row["image_id"]);
			if(
				ext_is_live("Ratings") && !is_null($image) &&
				strpos($user_ratings, $image->rating) === FALSE
			) {
				$image = null; // this is "clever", I may live to regret it
			}
			if(!is_null($image)) {
				$comments = $this->get_comments($image->id);
				$images[] = array($image, $comments);
			}
		}

		$this->theme->display_comment_list($images, $current_page, $total_pages, $user->can("create_comment"));
	}
// }}}

// get comments {{{
	/**
	 * @param string $query
	 * @param array $args
	 * @return Comment[]
	 */
	private function get_generic_comments($query, $args) {
		global $database;
		$rows = $database->get_all($query, $args);
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}

	/**
	 * @param int $count
	 * @return Comment[]
	 */
	private function get_recent_comments($count) {
		return $this->get_generic_comments("
			SELECT
				users.id as user_id, users.name as user_name, users.email as user_email, users.class as user_class,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
			FROM comments
			LEFT JOIN users ON comments.owner_id=users.id
			ORDER BY comments.id DESC
			LIMIT :limit
		", array("limit"=>$count));
	}

	/**
	 * @param int $user_id
	 * @param int $count
	 * @param int $offset
	 * @return Comment[]
	 */
	private function get_user_comments(/*int*/ $user_id, /*int*/ $count, /*int*/ $offset=0) {
		return $this->get_generic_comments("
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
		", array("user_id"=>$user_id, "offset"=>$offset, "limit"=>$count));
	}

	/**
	 * @param int $image_id
	 * @return Comment[]
	 */
	private function get_comments(/*int*/ $image_id) {
		return $this->get_generic_comments("
			SELECT
				users.id as user_id, users.name as user_name, users.email as user_email, users.class as user_class,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
			FROM comments
			LEFT JOIN users ON comments.owner_id=users.id
			WHERE comments.image_id=:image_id
			ORDER BY comments.id ASC
		", array("image_id"=>$image_id));
	}
// }}}

// add / remove / edit comments {{{
	/**
	 * @return bool
	 */
	private function is_comment_limit_hit() {
		global $config, $database;

		// sqlite fails at intervals
		if($database->get_driver_name() === "sqlite") return false;

		$window = int_escape($config->get_int('comment_window'));
		$max = int_escape($config->get_int('comment_limit'));

		if($database->get_driver_name() == "mysql") $window_sql = "interval $window minute";
		else $window_sql = "interval '$window minute'";

		// window doesn't work as an SQL param because it's inside quotes >_<
		$result = $database->get_all("
			SELECT *
			FROM comments
			WHERE owner_ip = :remote_ip AND posted > now() - $window_sql
		", array("remote_ip"=>$_SERVER['REMOTE_ADDR']));

		return (count($result) >= $max);
	}

	/**
	 * @return bool
	 */
	private function hash_match() {
		return ($_POST['hash'] == $this->get_hash());
	}

	/**
	 * get a hash which semi-uniquely identifies a submission form,
	 * to stop spam bots which download the form once then submit
	 * many times.
	 *
	 * FIXME: assumes comments are posted via HTTP...
	 *
	 * @return string
	 */
	public static function get_hash() {
		return md5($_SERVER['REMOTE_ADDR'] . date("%Y%m%d"));
	}

	/**
	 * @param string $text
	 * @return bool
	 */
	private function is_spam_akismet(/*string*/ $text) {
		global $config, $user;
		if(strlen($config->get_string('comment_wordpress_key')) > 0) {
			$comment = array(
				'author'       => $user->name,
				'email'        => $user->email,
				'website'      => '',
				'body'         => $text,
				'permalink'    => '',
				);

			# akismet breaks if there's no referrer in the environment; so if there
			# isn't, supply one manually
			if(!isset($_SERVER['HTTP_REFERER'])) {
				$comment['referrer'] = 'none';
				log_warning("comment", "User '{$user->name}' commented with no referrer: $text");
			}
			if(!isset($_SERVER['HTTP_USER_AGENT'])) {
				$comment['user_agent'] = 'none';
				log_warning("comment", "User '{$user->name}' commented with no user-agent: $text");
			}

			$akismet = new Akismet(
					$_SERVER['SERVER_NAME'],
					$config->get_string('comment_wordpress_key'),
					$comment);

			if($akismet->errorsExist()) {
				return false;
			}
			else {
				return $akismet->isSpam();
			}
		}

		return false;
	}

	/**
	 * @param int $image_id
	 * @param int $comment
	 * @return null
	 */
	private function is_dupe(/*int*/ $image_id, /*string*/ $comment) {
		global $database;
		return $database->get_row("
			SELECT *
			FROM comments
			WHERE image_id=:image_id AND comment=:comment
		", array("image_id"=>$image_id, "comment"=>$comment));
	}
// do some checks

	/**
	 * @param int $image_id
	 * @param User $user
	 * @param string $comment
	 * @throws CommentPostingException
	 */
	private function add_comment_wrapper(/*int*/ $image_id, User $user, /*string*/ $comment) {
		global $database, $page;

		if(!$user->can("bypass_comment_checks")) {
			// will raise an exception if anything is wrong
			$this->comment_checks($image_id, $user, $comment);
		}

		// all checks passed
		if($user->is_anonymous()) {
			$page->add_cookie("nocache", "Anonymous Commenter", time()+60*60*24, "/");
		}
		$database->Execute(
				"INSERT INTO comments(image_id, owner_id, owner_ip, posted, comment) ".
				"VALUES(:image_id, :user_id, :remote_addr, now(), :comment)",
				array("image_id"=>$image_id, "user_id"=>$user->id, "remote_addr"=>$_SERVER['REMOTE_ADDR'], "comment"=>$comment));
		$cid = $database->get_last_insert_id('comments_id_seq');
		$snippet = substr($comment, 0, 100);
		$snippet = str_replace("\n", " ", $snippet);
		$snippet = str_replace("\r", " ", $snippet);
		log_info("comment", "Comment #$cid added to Image #$image_id: $snippet", false, array("image_id"=>$image_id, "comment_id"=>$cid));
	}

	/**
	 * @param int $image_id
	 * @param User $user
	 * @param string $comment
	 * @throws CommentPostingException
	 */
	private function comment_checks(/*int*/ $image_id, User $user, /*string*/ $comment) {
		global $config, $page;

		// basic sanity checks
		if(!$user->can("create_comment")) {
			throw new CommentPostingException("Anonymous posting has been disabled");
		}
		else if(is_null(Image::by_id($image_id))) {
			throw new CommentPostingException("The image does not exist");
		}
		else if(trim($comment) == "") {
			throw new CommentPostingException("Comments need text...");
		}
		else if(strlen($comment) > 9000) {
			throw new CommentPostingException("Comment too long~");
		}

		// advanced sanity checks
		else if(strlen($comment)/strlen(gzcompress($comment)) > 10) {
			throw new CommentPostingException("Comment too repetitive~");
		}
		else if($user->is_anonymous() && !$this->hash_match()) {
			$page->add_cookie("nocache", "Anonymous Commenter", time()+60*60*24, "/");
			throw new CommentPostingException(
					"Comment submission form is out of date; refresh the ".
					"comment form to show you aren't a spammer~");
		}

		// database-querying checks
		else if($this->is_comment_limit_hit()) {
			throw new CommentPostingException("You've posted several comments recently; wait a minute and try again...");
		}
		else if($this->is_dupe($image_id, $comment)) {
			throw new CommentPostingException("Someone already made that comment on that image -- try and be more original?");
		}

		// rate-limited external service checks last
		else if($config->get_bool('comment_captcha') && !captcha_check()) {
			throw new CommentPostingException("Error in captcha");
		}
		else if($user->is_anonymous() && $this->is_spam_akismet($comment)) {
			throw new CommentPostingException("Akismet thinks that your comment is spam. Try rewriting the comment, or logging in.");
		}
	}
// }}}
}

