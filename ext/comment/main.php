<?php
require_once "lib/akismet.class.php";

/* CommentDeletionEvent {{{
 * CommentDeletionEvent:
 *   $comment_id
 *
 * A comment is being deleted. Maybe used by spam
 * detectors to get a feel for what should be delted
 * and what should be kept?
 */
class CommentDeletionEvent extends Event {
	var $comment_id;

	public function CommentDeletionEvent($comment_id) {
		$this->comment_id = $comment_id;
	}
}
// }}}

class Comment { // {{{
	public function Comment($row) {
		$this->owner_id = $row['user_id'];
		$this->owner_name = $row['user_name'];
		$this->comment =  $row['comment'];
		$this->comment_id =  $row['comment_id'];
		$this->image_id =  $row['image_id'];
		$this->poster_ip =  $row['poster_ip'];
		$this->posted =  $row['posted'];
	}
} // }}}

class CommentList extends Extension {
	var $theme;
// event handler {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("comment", "CommentListTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			$config->set_default_bool('comment_anon', true);
			$config->set_default_int('comment_window', 5);
			$config->set_default_int('comment_limit', 3);
			$config->set_default_int('comment_count', 5);

			if($config->get_int("ext_comments_version") < 2) {
				$this->install();
			}
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "comment")) {
			if($event->get_arg(0) == "add") {
				$this->add_comment_wrapper($_POST['image_id'], $_POST['comment']);
			}
			else if($event->get_arg(0) == "delete") {
				global $user;
				if($user->is_admin()) {
					// FIXME: post, not args
					if($event->count_args() == 3) {
						send_event(new CommentDeletionEvent($event->get_arg(1)));
						$event->page->set_mode("redirect");
						if(!empty($_SERVER['HTTP_REFERER'])) {
							$event->page->set_redirect($_SERVER['HTTP_REFERER']);
						}
						else {
							$event->page->set_redirect(make_link("post/view/".$event->get_arg(2)));
						}
					}
				}
				else {
					$this->theme->display_error($event->page, "Denied", "Only admins can delete comments");
				}
			}
			else if($event->get_arg(0) == "list") {
				$this->build_page($event->get_arg(1));
			}
		}

		if(is_a($event, 'PostListBuildingEvent')) {
			global $config;
			$cc = $config->get_int("comment_count");
			if($cc > 0) {
				$this->theme->display_recent_comments($event->page, $this->get_recent_comments($cc));
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			$this->theme->display_comments(
					$event->page,
					$this->get_comments($event->image->id),
					$this->can_comment(),
					$event->image->id);
		}

		if(is_a($event, 'ImageDeletionEvent')) {
			$this->delete_comments($event->image->id);
		}
		if(is_a($event, 'CommentDeletionEvent')) {
			$this->delete_comment($event->comment_id);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Comment Options");
			$sb->add_bool_option("comment_anon", "Allow anonymous comments: ");
			$sb->add_label("<br>Limit to ");
			$sb->add_int_option("comment_limit");
			$sb->add_label(" comments per ");
			$sb->add_int_option("comment_window");
			$sb->add_label(" minutes");
			$sb->add_label("<br>Show ");
			$sb->add_int_option("comment_count");
			$sb->add_label(" recent comments on the index");
			$sb->add_text_option("comment_wordpress_key", "<br>Akismet Key ");
			$event->panel->add_block($sb);
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;
		
		if($config->get_int("ext_comments_version") < 1) {
			$database->Execute("CREATE TABLE `comments` (
				`id` int(11) NOT NULL auto_increment,
				`image_id` int(11) NOT NULL,
				`owner_id` int(11) NOT NULL,
				`owner_ip` char(16) NOT NULL,
				`posted` datetime default NULL,
				`comment` text NOT NULL,
				PRIMARY KEY  (`id`),
				KEY `comments_image_id` (`image_id`)
			)");
			$config->set_int("ext_comments_version", 1);
		}

		if($config->get_int("ext_comments_version") == 1) {
			$database->Execute("CREATE INDEX comments_owner_ip ON comments(owner_ip)");
			$database->Execute("CREATE INDEX comments_posted ON comments(posted)");
			$config->set_int("ext_comments_version", 2);
		}
	}
// }}}
// page building {{{
	private function build_page($current_page) {
		global $page;
		global $database;

		if(is_null($current_page) || $current_page <= 0) {
			$current_page = 1;
		}

		$threads_per_page = 10;
		$start = $threads_per_page * ($current_page - 1);

		$get_threads = "
			SELECT image_id,MAX(posted) AS latest
			FROM comments
			GROUP BY image_id
			ORDER BY latest DESC
			LIMIT ?,?
			";
		$result = $database->Execute($get_threads, array($start, $threads_per_page));

		$total_pages = (int)($database->db->GetOne("SELECT COUNT(distinct image_id) AS count FROM comments") / 10);


		$this->theme->display_page_start($page, $current_page, $total_pages);

		$n = 10;
		while(!$result->EOF) {
			$image = $database->get_image($result->fields["image_id"]);
			$comments = $this->get_comments($image->id);
			$this->theme->add_comment_list($page, $image, $comments, $n, $this->can_comment());
			$n += 1;
			$result->MoveNext();
		}
	}
// }}}
// get comments {{{
	private function get_recent_comments() {
		global $config;
		global $database;
		$rows = $database->db->GetAll("
				SELECT
				users.id as user_id, users.name as user_name,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				ORDER BY comments.id DESC
				LIMIT ?
				", array($config->get_int('comment_count')));
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}
	
	private function get_comments($image_id) {
		global $config;
		global $database;
		$i_image_id = int_escape($image_id);
		$rows = $database->db->GetAll("
				SELECT
				users.id as user_id, users.name as user_name,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				WHERE comments.image_id=?
				ORDER BY comments.id ASC
				", array($i_image_id));
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

		$window = int_escape($config->get_int('comment_window'));
		$max = int_escape($config->get_int('comment_limit'));

		$result = $database->Execute("SELECT * FROM comments WHERE owner_ip = ? ".
				"AND posted > date_sub(now(), interval ? minute)",
				Array($_SERVER['REMOTE_ADDR'], $window));
		$recent_comments = $result->RecordCount();

		return ($recent_comments >= $max);
	}

	private function is_spam($text) {
		global $user;
		global $config;

		if(strlen($config->get_string('comment_wordpress_key')) == 0) {
			return false;
		}
		else {
			$comment = array(
				'author'       => $user->name,
				'email'        => $user->email,
				'website'      => '',
				'body'         => $text,
				'permalink'    => '',
				);

			$akismet = new Akismet(
					'http://www.yourdomain.com/',
					$config->get_string('comment_wordpress_key'),
					$comment);

			if($akismet->errorsExist()) {
				return false;
			}
			else {
				return $akismet->isSpam();
			}
		}
	}

	private function can_comment() {
		global $config;
		global $user;
		return ($config->get_bool('comment_anon') || !$user->is_anonymous());
	}

	private function add_comment_wrapper($image_id, $comment) {
		global $user;
		global $database;
		global $config;
		global $page;

		if(!$config->get_bool('comment_anon') && $user->is_anonymous()) {
			$this->theme->display_error($page, "Permission Denied", "Anonymous posting has been disabled");
		}
		else if(is_null($database->get_image($image_id))) {
			$this->theme->display_error($page, "Comment failed", "The image does not exist");
		}
		else if(trim($comment) == "") {
			$this->theme->display_error($page, "Comment Empty", "Comments need text...");
		}
		else if($this->is_comment_limit_hit()) {
			$this->theme->display_error($page, "Comment Limit Hit",
						"You've posted several comments recently; wait a minute and try again...");
		}
		else if($user->is_anonymous() && $this->is_spam($comment)) {
			$this->theme->display_error($page, "Spam Detected",
						"Akismet thinks that your comment is spam. Try rewriting the comment, or logging in.");
		}
		else {
			$database->Execute(
					"INSERT INTO comments(image_id, owner_id, owner_ip, posted, comment) ".
					"VALUES(?, ?, ?, now(), ?)",
					array($image_id, $user->id, $_SERVER['REMOTE_ADDR'], $comment));
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/".int_escape($image_id)));
		}
	}

	private function delete_comments($image_id) {
		global $database;
		$database->Execute("DELETE FROM comments WHERE image_id=?", array($image_id));
	}

	private function delete_comment($comment_id) {
		global $database;
		$database->Execute("DELETE FROM comments WHERE id=?", array($comment_id));
	}
// }}}
}
add_event_listener(new CommentList());
?>
