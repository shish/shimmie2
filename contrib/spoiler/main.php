<?php
/**
 * Name: Spoiler Filter
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://trac.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Adds a [spoiler] tag to rot13 text inside it
 */
class Spoiler extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'TextFormattingEvent')) {
			$event->formatted = $this->filter($event->formatted);
			$event->stripped  = $this->filter($event->stripped);
		}
	}
	
	private function filter($text) {
		$l1 = strlen("[spoiler]");
		$l2 = strlen("[/spoiler]");
		while(true) {
			$start = strpos($text, "[spoiler]");
			if($start === false) break;
			
			$end = strpos($text, "[/spoiler]");
			if($end === false) break;

			$beginning = substr($text, 0, $start);
			$middle = str_rot13(substr($text, $start+$l1, ($end-$start-$l1)));
			$ending = substr($text, $end + $l2, (strlen($text)-$end+$l2));

			$text = $beginning . $middle . $ending;
		}
		return $text;
	}
}
add_event_listener(new Spoiler(), 45); // before bbcode, so before <br>s are inserted
?>
