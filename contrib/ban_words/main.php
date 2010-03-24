<?php
/*
 * Name: Comment Word Ban
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: For stopping spam and other comment abuse
 * Documentation:
 *  Allows an administrator to ban certain words
 *  from comments. This can be a very simple but effective way
 *  of stopping spam; just add "viagra", "porn", etc to the
 *  banned words list.
 *  <p>Regex bans are also supported, allowing more complicated
 *  bans like <code>/http:.*\.cn\//</code> to block links to
 *  chinese websites, or <code>/.*?http.*?http.*?http.*?http.*?/</code>
 *  to block comments with four (or more) links in.
 *  <p>Note that for non-regex matches, only whole words are
 *  matched, eg banning "sex" would block the comment "get free
 *  sex call this number", but allow "This is a photo of Bob
 *  from Essex"
 */

class BanWords implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof InitExtEvent) {
			global $config;
			$config->set_default_string('banned_words', "
a href=
anal
blowjob
/buy-.*-online/
casino
cialis
doors.txt
fuck
hot video
kaboodle.com
lesbian
nexium
penis
/pokerst.*/
pornhub
porno
purchase
sex
sex tape
spinnenwerk.de
thx for all
TRAMADOL
ultram
very nice site
viagra
xanax
			");
		}

		if($event instanceof CommentPostingEvent) {
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
						throw new CommentPostingException("Comment contains banned terms");
					}
				}
				else {
					// other words are literal
					if(strpos($comment, $word) !== false) {
						throw new CommentPostingException("Comment contains banned terms");
					}
				}
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Banned Phrases");
			$sb->add_label("One per line, lines that start with slashes are treated as regex<br/>");
			$sb->add_longtext_option("banned_words");
			$event->panel->add_block($sb);
		}
	}
}
add_event_listener(new BanWords(), 30); // before the comment is added
?>
