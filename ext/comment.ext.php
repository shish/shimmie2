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
	}

	public function to_html($link_to_image = false) {
		global $user;

		$i_uid = int_escape($this->owner_id);
		$h_name = html_escape($this->owner_name);
		$h_poster_ip = html_escape($this->poster_ip);
		$h_comment = bbcode2html($this->comment);
		$i_comment_id = int_escape($this->comment_id);
		$i_image_id = int_escape($this->image_id);

		$h_userlink = "<a href='".make_link("user/$h_name")."'>$h_name</a>";
		$h_dellink = $user->is_admin() ? 
			"<br>($h_poster_ip, <a href='".make_link("comment/delete/$i_comment_id/$i_image_id")."'>Del</a>)" : "";
		$h_imagelink = $link_to_image ? "<a href='".make_link("post/view/$i_image_id")."'>&gt;&gt;&gt;</a>\n" : "";
		return "<p>$h_userlink: $h_comment $h_imagelink $h_dellink</p>";
	}
} // }}}

class CommentList extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_comments_version") < 1) {
				$this->install();
			}
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page == "comment")) {
			if($event->get_arg(0) == "add") {
				$this->add_comment_wrapper($_POST['image_id'], $_POST['comment']);
			}
			else if($event->get_arg(0) == "delete") {
				global $user;
				global $page;
				if($user->is_admin()) {
					// FIXME: post, not args
					if($event->count_args() == 3) {
						send_event(new CommentDeletionEvent($event->get_arg(1)));
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$event->get_arg(2)));
					}
				}
				else {
					// FIXME: denied message
				}
			}
			else if($event->get_arg(0) == "rss") {
				$this->build_rss();
			}
			else if($event->get_arg(0) == "list") {
				$this->build_page($event->get_arg(1));
			}
		}
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			global $page;
			global $config;
			if($config->get_int("comment_count") > 0) {
				$page->add_side_block(new Block("Comments", $this->build_recent_comments()), 50);
				// $page->add_quicknav("Comments", make_link("comments/list"));
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			$page->add_main_block(new Block("Comments",
						$this->build_image_comments($event->image->id).
						$this->build_postbox($event->image->id)), 50);
		}

		if(is_a($event, 'ImageDeletionEvent')) {
			$this->delete_comments($event->image->id);
		}
		if(is_a($event, 'CommentDeletionEvent')) {
			$this->delete_comment($event->comment_id);
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Comment Options");
			$sb->add_label("Allow anonymous comments ");
			$sb->add_bool_option("comment_anon");
			$sb->add_label("<br>Limit to ");
			$sb->add_int_option("comment_limit");
			$sb->add_label(" comments per ");
			$sb->add_int_option("comment_window");
			$sb->add_label(" minutes");
			$sb->add_label("<br>Show ");
			$sb->add_int_option("comment_count");
			$sb->add_label(" recent comments on the index");
			$sb->add_label("<br>Akismet Key ");
			$sb->add_text_option("comment_wordpress_key");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_bool("comment_anon", $_POST['comment_anon']);
			$event->config->set_int("comment_limit", $_POST['comment_limit']);
			$event->config->set_int("comment_window", $_POST['comment_window']);
			$event->config->set_int("comment_count", $_POST['comment_count']);
			$event->config->set_string("comment_wordpress_key", $_POST['comment_wordpress_key']);
		}
	}
// }}}
// installer {{{
	protected function install() {
		global $database;
		global $config;
		$database->db->Execute("CREATE TABLE `comments` (
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
// }}}
// page building {{{
	private function build_rss() {
		global $page;
		$page->set_mode("data");
		$page->set_type("application/rss+xml");

		$rss = "moo"; // FIXME

		$page->set_data($rss);
	}

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
		$result = $database->db->Execute($get_threads, array($start, $threads_per_page));


		$total_pages = (int)($database->db->GetOne("SELECT COUNT(distinct image_id) AS count FROM comments") / 10);

		$page->set_title("Comments");
		$page->set_heading("Comments");
		$page->add_side_block(new Block("Navigation", $this->build_navigation($current_page, $total_pages)));
		$page->add_main_block(new Paginator("comment/list", null, $current_page, $total_pages), 90);

		$n = 10;
		while(!$result->EOF) {
			$image = $database->get_image($result->fields["image_id"]);

			$html = "<div style='text-align: left'>";
			$html .= "<a href='".make_link("post/view/{$image->id}")."'>";
			$html .= "<img src='".($image->get_thumb_link())."' align='left' style='margin-right: 16px;'></a>";
			$html .= $this->build_image_comments($image->id);
			$html .= "</div>";
			$html .= "<div style='clear:both;'>".($this->build_postbox($image->id))."</div>";

			$page->add_main_block(new Block("{$image->id}: ".($image->get_tag_list()), $html), $n);
			$n += 1;
			$result->MoveNext();
		}

	}

	private function build_navigation($page_number, $total_pages) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$h_prev = ($page_number <= 1) ? "Prev" :
			"<a href='".make_link("comment/list/$prev")."'>Prev</a>";
		$h_index = "<a href='".make_link("index")."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" :
			"<a href='".make_link("comment/list/$next")."'>Next</a>";

		return "$h_prev | $h_index | $h_next";
	}

	private function build_image_comments($image_id) {
		global $config;
		$i_image_id = int_escape($image_id);
		$html = "<div id='image_comments'>";
		$html .= $this->query_to_html("
				SELECT
				users.id as user_id, users.name as user_name,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				WHERE comments.image_id=?
				ORDER BY comments.id ASC
				", array($i_image_id));
		$html .= "</div>";
		return $html;
	}

	private function build_recent_comments() {
		global $config;
		$html = $this->query_to_html("
				SELECT
				users.id as user_id, users.name as user_name,
				if(
					length(comments.comment) > 50,
					concat(substring(comments.comment, 1, 50), ' ...'),
					comments.comment
				  ) as comment,
				comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				ORDER BY comments.id DESC
				LIMIT ?
				", array($config->get_int('comment_count')), true);
		$html .= "<p><a href='".make_link("comment/list")."'>Full List &gt;&gt;&gt;</a>";
		return $html;
	}

	private function build_postbox($image_id) {
		if($this->can_comment()) {
			$i_image_id = int_escape($image_id);
			return "
				<form action='".make_link("comment/add")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<textarea name='comment' rows='5' cols='50'></textarea>
				<br><input type='submit' value='Post' />
				</form>
				";
		}
		else {
			return "<p><small>You need to create an account before you can comment</small></p>";
		}
	}

	private function query_to_html($query, $args, $link_to_image=false) {
		global $database;
		global $config;

		$html = "";
		$result = $database->db->Execute($query, $args);
		while(!$result->EOF) {
			$comment = new Comment($result->fields);
			$html .= $comment->to_html($link_to_image);
			$result->MoveNext();
		}
		return $html;
	}
// }}}
// add / remove / edit comments {{{
	private function is_comment_limit_hit() {
		global $user;
		global $config;
		global $database;

		$window = int_escape($config->get_int('comment_window'));
		$max = int_escape($config->get_int('comment_limit'));

		$result = $database->db->Execute("SELECT * FROM comments WHERE owner_ip = ? ".
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

		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_side_block(new NavBlock());
		if(!$config->get_bool('comment_anon') && $user->is_anonymous()) {
			$page->add_main_block(new Block("Permission Denied", "Anonymous posting has been disabled"));
		}
		else if(trim($comment) == "") {
			$page->add_main_block(new Block("Comment Empty", "Comments need text..."));
		}
		else if($this->is_comment_limit_hit()) {
			$page->add_main_block(new Block("Comment Limit Hit",
						"You've posted several comments recently; wait a minute and try again..."));
		}
		else if($this->is_spam($comment)) {
			$page->add_main_block(new Block("Spam Detected",
						"Akismet thinks that your comment is spam. Try rewriting the comment?"));
		}
		else {
			$database->db->Execute(
					"INSERT INTO comments(image_id, owner_id, owner_ip, posted, comment) ".
					"VALUES(?, ?, ?, now(), ?)",
					array($image_id, $user->id, $_SERVER['REMOTE_ADDR'], $comment));
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/".int_escape($image_id)));
		}
	}

	private function delete_comments($image_id) {
		global $database;
		$database->db->Execute("DELETE FROM comments WHERE image_id=?", array($image_id));
	}

	private function delete_comment($comment_id) {
		global $database;
		$database->db->Execute("DELETE FROM comments WHERE id=?", array($comment_id));
	}
// }}}
}
add_event_listener(new CommentList());
?>
