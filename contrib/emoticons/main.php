<?php
/**
 * Name: Emoticon Filter
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Turn :smile: into a link to smile.gif
 */

class Emoticons implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof TextFormattingEvent) {
			$event->formatted = $this->bbcode_to_html($event->formatted);
			$event->stripped  = $this->bbcode_to_text($event->stripped);
		}
	}
	
	private function bbcode_to_html($text) {
		$data_href = get_base_href();
		$text = preg_replace("/:([a-z]*?):/s", "<img src='$data_href/ext/emoticons/default/\\1.gif'>", $text);
		return $text;
	}

	private function bbcode_to_text($text) {
		// $text = preg_replace("/:([a-z]*?):/s", "\\1", $text);
		return $text;
	}
}
add_event_listener(new Emoticons());
?>
