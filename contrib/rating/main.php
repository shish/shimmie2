<?php
/**
 * Name: Image Ratings
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to rate images
 */

class Ratings extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_ratings_version") < 1) {
				$this->install();
			}
		}

/*
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
			$sb->add_int_option("comment_limit", 1, 60);
			$sb->add_label(" comments per ");
			$sb->add_int_option("comment_window", 1, 60);
			$sb->add_label(" minutes");
			$sb->add_label("<br>Show ");
			$sb->add_int_option("comment_count", 0, 100);
			$sb->add_label(" recent comments on the index");
			$event->panel->add_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_bool("comment_anon", $_POST['comment_anon']);
			$event->config->set_int("comment_limit", $_POST['comment_limit']);
			$event->config->set_int("comment_window", $_POST['comment_window']);
			$event->config->set_int("comment_count", $_POST['comment_count']);
		}
*/
	}

	private function can_comment() {
		global $config, $user;
		return $config->get_bool("rate_anon") || ($user->id != $config->get_int("anon_id"));
	}
// }}} 
// installer {{{
	protected function install() {
		global $database;
		global $config;
		$database->Execute("CREATE TABLE `image_voters` (
			`image_id` int(11) NOT NULL,
			`user_id` int(11) NOT NULL,
			`vote` tinyint(4) NOT NULL,
			`voted` datetime NOT NULL,
			PRIMARY KEY  (`image_id`,`user_id`)
		)");
		$config->set_int("ext_ratings_version", 1);
	}
// }}}
// page building {{{

	private function build_image_rating($image_id) {
		global $config;
		$i_image_id = int_escape($image_id);
		return $this->query_to_html("
				SELECT
				users.id as user_id, users.name as user_name,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				WHERE comments.image_id=?
				ORDER BY comments.id ASC
				LIMIT ?
		", array($i_image_id, $config->get_int('recent_count')));
	}

	private function build_rater($image_id) {
		if($this->can_comment()) {
			$i_image_id = int_escape($image_id);
			return "
				<form action='".make_link("rating/vote_up")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='submit' value='Vote Up' />
				</form>
				<form action='".make_link("rating/vote_down")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id' />
				<input type='submit' value='Vote Down' />
				</form>
			";
		}
		else {
			return "You need to create an account before you can rate";
		}
	}
// }}}
// add / remove / edit comments {{{
	private function add_rating($image_id, $rating) {
		global $user;
		global $database;
		global $config;
		global $page;

		$page->set_title("Error");
		$page->set_heading("Error");
		if(!$config->get_bool('rating_anon') && $user->is_anonymous()) {
			$page->add_main_block(new Block("Permission Denied", "Anonymous rating has been disabled"));
		}
		else {
			$i_rating = int_escape($rating);
			$database->Execute(
					"INSERT INTO image_ratings(image_id, user_id, rating, rated) ".
					"VALUES(?, ?, ?, now())",
					array($image_id, $user->id, $i_rating));
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/".int_escape($image_id)));
		}
	}

	private function delete_ratings($image_id) {
		global $database;
		$database->Execute("DELETE FROM image_voters WHERE image_id=?", array($image_id));
	}
// }}}
}
add_event_listener(new Ratings());
?>
