<?php
/**
 * Name: Emoticon Filter
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Lets users use graphical smilies
 * Documentation:
 *  This extension will turn colon-something-colon into a link
 *  to an image with that something as the name, eg :smile:
 *  becomes a link to smile.gif
 *  <p>Images are stored in /ext/emoticons/default/, and you can
 *  add more emoticons by uploading images into that folder.
 */

class Emoticons extends FormatterExtension {
	public function format($text) {
		$data_href = get_base_href();
		$text = preg_replace("/:([a-z]*?):/s", "<img src='$data_href/ext/emoticons/default/\\1.gif'>", $text);
		return $text;
	}

	public function strip($text) {
		return $text;
	}
}
add_event_listener(new Emoticons());

class EmoticonList extends SimpleExtension {
	public function onPageRequest($event) {
		if($event->page_matches("emote/list")) {
			$this->theme->display_emotes(glob("ext/emoticons/default/*"));
		}
	}
}
?>
