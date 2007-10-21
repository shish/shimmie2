<?php
/**
 * Name: Image Scores
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to score images
 */

class ScoreSetEvent extends Event {
	var $image_id, $user, $score;

	public function ScoreSetEvent($image_id, $user, $score) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->score = $score;
	}
}

class Score extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("score", "ScoreTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_score_version", 0) < 2) {
				$this->install();
			}

			global $config;
			$config->set_default_string("ext_rating_anon_privs", 'sq');
			$config->set_default_string("ext_rating_user_privs", 'sq');
		}

		if(is_a($event, 'PageRequestEvent') && $event->page_name == "score" &&
				$event->get_arg(0) == "set" && $event->user->is_admin() &&
				isset($_POST['score']) && isset($_POST['image_id'])) {
			$i_score = int_escape($_POST['score']);
			$i_image_id = int_escape($_POST['image_id']);
			
			if($i_score >= -2 || $i_score <= 2) {
				send_event(new ScoreSetEvent($i_image_id, $event->user->id, $i_score));
			}
			
			$event->page->set_mode("redirect");
			$event->page->set_redirect(make_link("post/view/$i_image_id"));
		}
		
		if(is_a($event, 'ScoreSetEvent')) {
			$this->add_vote($event->image_id, $event->user->id, $event->score);
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			// TODO: scorer vs voter
			$this->theme->display_scorer($event->page, $event->image->id, $event->image->score);
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			/*
			TODO: disable anon voting
			TODO: switch between average and sum modes
			*/
		}
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_score_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN score INTEGER NOT NULL DEFAULT 0");
			$database->Execute("CREATE INDEX images__score ON images(score)");
			$database->Execute("
				CREATE TABLE images_score_votes (
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					score INTEGER NOT NULL,
					UNIQUE(image_id, user_id),
					INDEX(image_id)
				)
			");
			$config->set_int("ext_score_version", 1);
		}
		if($config->get_int("ext_score_version") < 2) {
			$database->Execute("ALTER TABLE images CHANGE score vote_score INTEGER NOT NULL DEFAULT 0");
			$config->set_int("ext_score_version", 2);
		}
	}

	private function add_vote($image_id, $user_id, $score) {
		global $database;
		// TODO: update if already voted
		$database->Execute(
			"INSERT INTO images_score_votes(image_id, user_id, score) VALUES(?, ?, ?)",
			array($image_id, $user_id, $score));
		$database->Execute(
			"UPDATE images SET vote_score=(SELECT AVG(score) FROM images_score_votes WHERE image_id=?) WHERE id=?",
			array($image_id, $image_id));
	}
}
add_event_listener(new Score());
?>
