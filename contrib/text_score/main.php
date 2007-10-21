<?php
/**
 * Name: Image Scores (Text)
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allow users to score images
 */

class TextScoreSetEvent extends Event {
	var $image_id, $user, $score;

	public function TextScoreSetEvent($image_id, $user, $score) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->score = $score;
	}
}

class TextScore extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("text_score", "TextScoreTheme");

		if(is_a($event, 'InitExtEvent')) {
			global $config;
			if($config->get_int("ext_text_score_version", 0) < 1) {
				$this->install();
			}
			$config->set_default_bool("text_score_anon", true);
		}

		if(is_a($event, 'PageRequestEvent') && $event->page_name == "text_score" &&
				$event->get_arg(0) == "vote" && $event->user->is_admin() &&
				isset($_POST['score']) && isset($_POST['image_id'])) {
			$i_score = int_escape($_POST['score']);
			$i_image_id = int_escape($_POST['image_id']);
			
			if($i_score >= -2 || $i_score <= 2) {
				send_event(new TextScoreSetEvent($i_image_id, $event->user, $i_score));
			}
			
			$event->page->set_mode("redirect");
			$event->page->set_redirect(make_link("post/view/$i_image_id"));
		}
		
		if(is_a($event, 'TextScoreSetEvent')) {
			if(!$event->user->is_anonymous() || $config->get_bool("text_score_anon")) {
				$this->add_vote($event->image_id, $event->user->id, $event->score);
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $user;
			if(!$user->is_anonymous() || $config->get_bool("text_score_anon")) {
				$this->theme->display_scorer($event->page, $event->image->id, $event->image->text_score);
			}
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Text Score");
			$sb->add_bool_option("text_score_anon", "Allow anonymous votes: ");
			$event->panel->add_block($sb);
		}
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_text_score_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN text_score INTEGER NOT NULL DEFAULT 0");
			$database->Execute("CREATE INDEX images__text_score ON images(text_score)");
			$database->Execute("
				CREATE TABLE text_score_votes (
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					score INTEGER NOT NULL,
					UNIQUE(image_id, user_id),
					INDEX(image_id)
				)
			");
			$config->set_int("ext_text_score_version", 1);
		}
	}

	private function add_vote($image_id, $user_id, $score) {
		global $database;
		// TODO: update if already voted
		$database->Execute(
			"INSERT INTO text_score_votes(image_id, user_id, score) VALUES(?, ?, ?)",
			array($image_id, $user_id, $score));
		$database->Execute(
			"UPDATE images SET text_score=(SELECT AVG(score) FROM text_score_votes WHERE image_id=?) WHERE id=?",
			array($image_id, $image_id));
	}
}
add_event_listener(new TextScore());
?>
