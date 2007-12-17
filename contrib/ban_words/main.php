<?php
/**
 * Name: Comment Word Ban
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: For stopping spam and other comment abuse
 */

class BanWords extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			$config->set_default_string('banned_words', "
viagra
porn
			");
		}

		if(is_a($event, 'CommentPostingEvent')) {
			global $config;
			$banned = $config->get_string("banned_words");
			$comment = strtolower($event->comment);
			
			foreach(explode("\n", $banned) as $word) {
				$word = trim(strtolower($word));
				if(strlen($word) == 0) {
					// line is blank
					continue;
				}
				else if($word[0] == '/') {
					// lines that start with slash are regex
					if(preg_match($word, $comment)) {
						$event->veto("Comment contains banned terms");
						break;
					}
				}
				else {
					// other words are literal
					if(strpos($comment, $word) !== false) {
						$event->veto("Comment contains banned terms");
						break;
					}
				}
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Banned Phrases");
			$sb->add_label("One per line, lines that start with slashes are treated as regex<br/>");
			$sb->add_longtext_option("banned_words");
			$event->panel->add_block($sb);
		}
	}
}
add_event_listener(new BanWords(), 30); // before the comment is added
?>
