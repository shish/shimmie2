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
	var $image_id, $user, $comment;

	public function CommentPostingEvent($image_id, $user, $comment) {
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
	var $comment_id;

	public function CommentDeletionEvent($comment_id) {
		$this->comment_id = $comment_id;
	}
}

class CommentPostingException extends SCoreException {}

class Comment {
	public function Comment($row) {
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

	public static function count_comments_by_user($user) {
		global $database;
		return $database->get_one("SELECT COUNT(*) AS count FROM comments WHERE owner_id=:owner_id", array("owner_id"=>$user->id));
	}

	public function get_owner() {
		if(empty($this->owner)) $this->owner = User::by_id($this->owner_id);
		return $this->owner;
	}
}

class CommentList extends Extension {
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
					posted DATETIME DEFAULT NULL,
					comment TEXT NOT NULL,
					INDEX (image_id),
					INDEX (owner_ip),
					INDEX (posted),
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
					FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT
				");
				$config->set_int("ext_comments_version", 3);
			}

			// the whole history
			if($config->get_int("ext_comments_version") < 1) {
				$database->create_table("comments", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					owner_id INTEGER NOT NULL,
					owner_ip CHAR(16) NOT NULL,
					posted DATETIME DEFAULT NULL,
					comment TEXT NOT NULL,
					INDEX (image_id)
				");
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
		global $page, $user, $database;
		if($event->page_matches("comment")) {
			if($event->get_arg(0) === "add") {
				if(isset($_POST['image_id']) && isset($_POST['comment'])) {
					try {
						$i_iid = int_escape($_POST['image_id']);
						$cpe = new CommentPostingEvent($_POST['image_id'], $user, $_POST['comment']);
						send_event($cpe);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/$i_iid#comment_on_$i_iid"));
					}
					catch(CommentPostingException $ex) {
						$this->theme->display_error(403, "Comment Blocked", $ex->getMessage());
					}
				}
			}
			else if($event->get_arg(0) === "delete") {
				if($user->can("delete_comment")) {
					// FIXME: post, not args
					if($event->count_args() === 3) {
						send_event(new CommentDeletionEvent($event->get_arg(1)));
						flash_message("Deleted comment");
						$page->set_mode("redirect");
						if(!empty($_SERVER['HTTP_REFERER'])) {
							$page->set_redirect($_SERVER['HTTP_REFERER']);
						}
						else {
							$page->set_redirect(make_link("post/view/".$event->get_arg(2)));
						}
					}
				}
				else {
					$this->theme->display_permission_denied();
				}
			}
			else if($event->get_arg(0) === "bulk_delete") {
				if($user->can("delete_comment") && !empty($_POST["ip"])) {
					$ip = $_POST['ip'];

					$cids = $database->get_col("SELECT id FROM comments WHERE owner_ip=:ip", array("ip"=>$ip));
					$num = count($cids);
					log_warning("comment", "Deleting $num comments from $ip");
					foreach($cids as $cid) {
						send_event(new CommentDeletionEvent($cid));
					}
					flash_message("Deleted $num comments");

					$page->set_mode("redirect");
					$page->set_redirect(make_link("admin"));
				}
				else {
					$this->theme->display_permission_denied();
				}
			}
			else if($event->get_arg(0) === "list") {
				$page_num = int_escape($event->get_arg(1));
				$this->build_page($page_num);
			}
			else if($event->get_arg(0) === "beta-search") {
				$search = $event->get_arg(1);
				$page_num = int_escape($event->get_arg(2));
				$duser = User::by_name($search);
				$i_comment_count = Comment::count_comments_by_user($duser);
				$com_per_page = 50;
				$total_pages = ceil($i_comment_count/$com_per_page);
				$page_num = $this->sanity_check_pagenumber($page_num, $total_pages);
				$comments = $this->get_user_comments($duser->id, $com_per_page, ($page_num-1) * $com_per_page);
				$this->theme->display_all_user_comments($comments, $page_num, $total_pages, $duser);
			}
		}
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
		$database->Execute("DELETE FROM comments WHERE id=:comment_id", array("comment_id"=>$event->comment_id));
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
		if(preg_match("/comments(<|>|<=|>=|=)(\d+)/", $event->term, $matches)) {
			$cmp = $matches[1];
			$comments = $matches[2];
			$event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM comments GROUP BY image_id HAVING count(image_id) $cmp $comments)"));
		}
		else if(preg_match("/commented_by=(.*)/i", $event->term, $matches)) {
			global $database;
			$user = User::by_name($matches[1]);
			if(!is_null($user)) {
				$user_id = $user->id;
			}
			else {
				$user_id = -1;
			}

			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
		}
		else if(preg_match("/commented_by_userid=([0-9]+)/i", $event->term, $matches)) {
			$user_id = int_escape($matches[1]);
			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM comments WHERE owner_id = $user_id)"));
		}
	}

// page building {{{
	private function build_page(/*int*/ $current_page) {
		global $page, $config, $database, $user;

		if(class_exists("Ratings")) {
			$user_ratings = Ratings::get_user_privs($user);
		}
		$total_pages = $database->cache->get("comment_pages");
		if(is_null($current_page) || $current_page <= 0) {
			$current_page = 1;
		}
		$current_page = $this->sanity_check_pagenumber($current_page, $total_pages);
		$threads_per_page = 10;
		$start = $threads_per_page * ($current_page - 1);

		$where = SPEED_HAX ? "WHERE posted > now() - interval '24 hours'" : "";
		$get_threads = "
			SELECT image_id,MAX(posted) AS latest
			FROM comments $where
			GROUP BY image_id
			ORDER BY latest DESC
			LIMIT :limit OFFSET :offset
			";
		$result = $database->Execute($get_threads, array("limit"=>$threads_per_page, "offset"=>$start));

		if(empty($total_pages)) {
			$total_pages = (int)($database->get_one("SELECT COUNT(c1) FROM (SELECT COUNT(image_id) AS c1 FROM comments $where GROUP BY image_id) AS s1") / 10);
			$database->cache->set("comment_pages", $total_pages, 600);
		}

		$images = array();
		while($row = $result->fetch()) {
			$image = Image::by_id($row["image_id"]);
			$comments = $this->get_comments($image->id);
			if(class_exists("Ratings")) {
				if(strpos($user_ratings, $image->rating) === FALSE) {
					$image = null; // this is "clever", I may live to regret it
				}
			}
			if(!is_null($image)) $images[] = array($image, $comments);
		}

		$this->theme->display_comment_list($images, $current_page, $total_pages, $user->can("create_comment"));
	}
// }}}
// get comments {{{
	private function get_recent_comments($count) {
		global $config;
		global $database;
		$rows = $database->get_all("
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
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}

	private function get_user_comments(/*int*/ $user_id, /*int*/ $count, /*int*/ $offset=0) {
		global $config;
		global $database;
		$rows = $database->get_all("
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
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}

	private function get_comments(/*int*/ $image_id) {
		global $config;
		global $database;
		$i_image_id = int_escape($image_id);
		$rows = $database->get_all("
				SELECT
				users.id as user_id, users.name as user_name, users.email as user_email, users.class as user_class,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				WHERE comments.image_id=:image_id
				ORDER BY comments.id ASC
				", array("image_id"=>$i_image_id));
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}
// }}}
// add / remove / edit comments {{{
	private function is_comment_limit_hit() {
		global $user;
		global $config;
		global $database;

		// sqlite fails at intervals
		if($database->get_driver_name() === "sqlite") return false;

		$window = int_escape($config->get_int('comment_window'));
		$max = int_escape($config->get_int('comment_limit'));

		if($database->get_driver_name() == "mysql") $window_sql = "interval $window minute";
		else $window_sql = "interval '$window minute'";

		// window doesn't work as an SQL param because it's inside quotes >_<
		$result = $database->get_all("SELECT * FROM comments WHERE owner_ip = :remote_ip ".
				"AND posted > now() - $window_sql",
				Array("remote_ip"=>$_SERVER['REMOTE_ADDR']));

		return (count($result) >= $max);
	}

	private function hash_match() {
		return ($_POST['hash'] == $this->get_hash());
	}

	/**
	 * get a hash which semi-uniquely identifies a submission form,
	 * to stop spam bots which download the form once then submit
	 * many times.
	 *
	 * FIXME: assumes comments are posted via HTTP...
	 */
	public static function get_hash() {
		return md5($_SERVER['REMOTE_ADDR'] . date("%Y%m%d"));
	}

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

	private function is_dupe(/*int*/ $image_id, /*string*/ $comment) {
		global $database;
		return ($database->get_row("SELECT * FROM comments WHERE image_id=:image_id AND comment=:comment", array("image_id"=>$image_id, "comment"=>$comment)));
	}
// do some checks
	private function sanity_check_pagenumber($pagenum, $maxpage){
		if (!is_numeric($pagenum)){
			$pagenum=1;
		}
		if ($pagenum>$maxpage){
			$pagenum=$maxpage;
		}
		if ($pagenum<=0){
			$pagenum=1;
		}
		return $pagenum;
	}
	private function add_comment_wrapper(/*int*/ $image_id, User $user, /*string*/ $comment) {
		global $database;
		global $config;

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
			set_prefixed_cookie("nocache", "Anonymous Commenter", time()+60*60*24, "/");
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

		// all checks passed
		else {
			if($user->is_anonymous()) {
				set_prefixed_cookie("nocache", "Anonymous Commenter", time()+60*60*24, "/");
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
	}
// }}}
}
?>
