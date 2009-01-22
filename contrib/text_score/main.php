<?php
/**
 * Name: Image Scores (Text)
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allow users to score images
 * Documentation:
 *  Similar to the Image Scores (Numeric) extension, but this one
 *  uses an average rather than the sum of all votes, which means
 *  that the score will be [-2 .. +2], and each integer in that
 *  range has a label attached.
 */

class TextScoreSetEvent extends Event {
	var $image_id, $user, $score;

	public function TextScoreSetEvent($image_id, $user, $score) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->score = $score;
	}
}

class TextScore implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof InitExtEvent)) {
			global $config;
			if($config->get_int("ext_text_score_version", 0) < 1) {
				$this->install();
			}
			$config->set_default_bool("text_score_anon", true);
		}

		if(($event instanceof ImageInfoBoxBuildingEvent)) {
			global $user;
			global $config;
			if(!$user->is_anonymous() || $config->get_bool("text_score_anon")) {
				$event->add_part($this->theme->get_scorer_html($event->image));
			}
		}

		if($event instanceof ImageInfoSetEvent) {
			global $user;
			$i_score = int_escape($_POST['text_score__score']);

			if($i_score >= -2 || $i_score <= 2) {
				send_event(new TextScoreSetEvent($event->image_id, $user, $i_score));
			}
		}

		if(($event instanceof TextScoreSetEvent)) {
			if(!$event->user->is_anonymous() || $config->get_bool("text_score_anon")) {
				$this->add_vote($event->image_id, $event->user->id, $event->score);
			}
		}

		if(($event instanceof ImageDeletionEvent)) {
			global $database;
			$database->execute("DELETE FROM text_score_votes WHERE image_id=?", array($event->image->id));
		}

		if(($event instanceof SetupBuildingEvent)) {
			$sb = new SetupBlock("Text Score");
			$sb->add_bool_option("text_score_anon", "Allow anonymous votes: ");
			$event->panel->add_block($sb);
		}

		if(($event instanceof ParseLinkTemplateEvent)) {
			$event->replace('$text_score', $this->theme->score_to_name($event->image->text_score));
		}
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_text_score_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN text_score INTEGER NOT NULL DEFAULT 0");
			$database->Execute("CREATE INDEX images__text_score ON images(text_score)");
			$database->create_table("text_score_votes", "
				image_id INTEGER NOT NULL REFERENCES images(id) ON DELETE CASCADE,
				user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
				score INTEGER NOT NULL,
				UNIQUE(image_id, user_id),
				INDEX(image_id)
			");
			$config->set_int("ext_text_score_version", 1);
		}
	}

	private function add_vote($image_id, $user_id, $score) {
		global $database;
		// TODO: update if already voted
		$database->Execute(
			"REPLACE INTO text_score_votes(image_id, user_id, score) VALUES(?, ?, ?)",
			array($image_id, $user_id, $score));
		$database->Execute(
			"UPDATE images SET text_score=(SELECT AVG(score) FROM text_score_votes WHERE image_id=?) WHERE id=?",
			array($image_id, $image_id));
	}
}
add_event_listener(new TextScore());
?>
