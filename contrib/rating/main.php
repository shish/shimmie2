<?php
/**
 * Name: Image Ratings
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to rate images
 */

class Ratings extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("rating", "RatingsTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_ratings_version") < 1) {
				$this->install();
			}
		}

		if(is_a($event, 'PageRequestEvent') && $event->page_name == "rating" &&
				$event->get_arg(0) == "set" && $event->user->is_admin() &&
				isset($_POST['rating']) && isset($_POST['image_id'])) {
			$this->set_rating($_POST['image_id'], $_POST['rating']);
			$event->page->set_mode("redirect");
			$event->page->set_redirect(make_link("post/view/".int_escape($_POST['image_id'])));
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			if($user->is_admin()) {
				$this->theme->display_rater($event->page, $event->image->id);
			}
		}
	}

	private function install() {
		global $database;
		global $config;
		$database->Execute("ALTER TABLE images
			ADD COLUMN rating ENUM('s', 'q', 'e') NOT NULL DEFAULT 'q'
		)");
		$config->set_int("ext_ratings_version", 1);
	}

	private function set_rating($image_id, $rating) {
		global $database;
		$database->Execute("UPDATE images SET rating=? WHERE id=?", array($rating, $image_id));
	}
}
add_event_listener(new Ratings());
?>
