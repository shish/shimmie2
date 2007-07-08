<?php

class BBCode extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'TextFormattingEvent')) {
			$event->formatted = $this->bbcode_to_html($event->formatted);
			$event->stripped  = $this->bbcode_to_text($event->stripped);
		}
	}
	
	private function bbcode_to_html($text) {
		$text = trim($text);
		$text = html_escape($text);
		$text = preg_replace("/\[b\](.*?)\[\/b\]/s", "<b>\\1</b>", $text);
		$text = preg_replace("/\[i\](.*?)\[\/i\]/s", "<i>\\1</i>", $text);
		$text = preg_replace("/\[u\](.*?)\[\/u\]/s", "<u>\\1</u>", $text);
		$text = preg_replace("/\[code\](.*?)\[\/code\]/s", "<pre>\\1</pre>", $text);
		$text = preg_replace("/&gt;&gt;(\d+)/s",
			"<a href='".make_link("post/view/\\1")."'>&gt;&gt;\\1</a>", $text);
		$text = preg_replace("/\[url=((?:https?|ftp|irc):\/\/.*?)\](.*?)\[\/url\]/s", "<a href='\\1'>\\2</a>", $text);
		$text = preg_replace("/\[url\]((?:https?|ftp|irc):\/\/.*?)\[\/url\]/s", "<a href='\\1'>\\1</a>", $text);
		$text = preg_replace("/\[\[(.*?)\]\]/s", 
			"<a href='".make_link("wiki/\\1")."'>\\1</a>", $text);
		$text = str_replace("\n", "\n<br>", $text);
		return $text;
	}

	private function bbcode_to_text($text) {
		$text = trim($text);
		$text = html_escape($text);
		$text = preg_replace("/\[b\](.*?)\[\/b\]/s", "\\1", $text);
		$text = preg_replace("/\[i\](.*?)\[\/i\]/s", "\\1", $text);
		$text = preg_replace("/\[u\](.*?)\[\/u\]/s", "\\1", $text);
		$text = preg_replace("/\[code\](.*?)\[\/code\]/s", "\\1", $text);
		$text = preg_replace("/\[url=(.*?)\](.*?)\[\/url\]/s", "\\2", $text);
		$text = preg_replace("/\[url\](.*?)\[\/url\]/s", "\\1", $text);
		$text = preg_replace("/\[\[(.*?)\]\]/s", "\\1", $text);
		return $text;
	}
}
add_event_listener(new BBCode());
?>
