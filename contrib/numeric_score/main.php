<?php
/**
 * Name: Image Scores (Numeric)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to score images
 */

class NumericScoreSetEvent extends Event {
	var $image_id, $user, $score;

	public function NumericScoreSetEvent($image_id, $user, $score) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->score = $score;
	}
}

class NumericScore extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("numeric_score", "NumericScoreTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_numeric_score_version", 0) < 1) {
				$this->install();
			}
			$config->set_default_bool("numeric_score_anon", true);
		}

		if(is_a($event, 'PageRequestEvent') && $event->page_name == "numeric_score" &&
				$event->get_arg(0) == "vote" &&
				isset($_POST['score']) && isset($_POST['image_id'])) {
			$i_score = int_escape($_POST['score']);
			$i_image_id = int_escape($_POST['image_id']);
			
			if($i_score >= -1 || $i_score <= 1) {
				send_event(new NumericScoreSetEvent($i_image_id, $event->user, $i_score));
			}
			
			$event->page->set_mode("redirect");
			$event->page->set_redirect(make_link("post/view/$i_image_id"));
		}
		
		if(is_a($event, 'NumericScoreSetEvent')) {
			if(!$event->user->is_anonymous() || $config->get_bool("numeric_score_anon")) {
				$this->add_vote($event->image_id, $event->user->id, $event->score);
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			global $config;
			if(!$user->is_anonymous() || $config->get_bool("numeric_score_anon")) {
				$this->theme->display_voter($event->page, $event->image->id, $event->image->numeric_score);
			}
		}
		
		if(is_a($event, 'ImageDeletionEvent')) {
			global $database;
			$database->execute("DELETE FROM numeric_score_votes WHERE image_id=?", array($event->image->id));
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Numeric Score");
			$sb->add_bool_option("numeric_score_anon", "Allow anonymous votes: ");
			$event->panel->add_block($sb);
		}
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_numeric_score_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN numeric_score INTEGER NOT NULL DEFAULT 0");
			$database->Execute("CREATE INDEX images__numeric_score ON images(numeric_score)");
			$database->Execute("
				CREATE TABLE numeric_score_votes (
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					score INTEGER NOT NULL,
					UNIQUE(image_id, user_id),
					INDEX(image_id)
				)
			");
			$config->set_int("ext_numeric_score_version", 1);
		}
	}

	private function add_vote($image_id, $user_id, $score) {
		global $database;
		$database->Execute(
			"REPLACE INTO numeric_score_votes(image_id, user_id, score) VALUES(?, ?, ?)",
			array($image_id, $user_id, $score));
		$database->Execute(
			"UPDATE images SET numeric_score=(SELECT SUM(score) FROM numeric_score_votes WHERE image_id=?) WHERE id=?",
			array($image_id, $image_id));
	}
}
add_event_listener(new NumericScore());
?>
