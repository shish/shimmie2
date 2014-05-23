<?php
/*
 * Name: Emoticon Filter
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Lets users use graphical smilies
 * Documentation:
 *  This extension will turn colon-something-colon into a link
 *  to an image with that something as the name, eg :smile:
 *  becomes a link to smile.gif
 *  <p>Images are stored in /ext/emoticons/default/, and you can
 *  add more emoticons by uploading images into that folder.
 */

/**
 * Class Emoticons
 */
class Emoticons extends FormatterExtension {
	/**
	 * @param string $text
	 * @return string
	 */
	public function format(/*string*/ $text) {
		$data_href = get_base_href();
		$text = preg_replace("/:([a-z]*?):/s", "<img src='$data_href/ext/emoticons/default/\\1.gif'>", $text);
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function strip(/*string*/ $text) {
		return $text;
	}
}

/**
 * Class EmoticonList
 */
class EmoticonList extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("emote/list")) {
			$this->theme->display_emotes(glob("ext/emoticons/default/*"));
		}
	}
}

