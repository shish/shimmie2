<?php
/**
 * Name: Word Filter
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Simple search and replace
 */

class WordFilter implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof TextFormattingEvent) {
			$event->formatted = $this->filter($event->formatted);
			$event->stripped  = $this->filter($event->stripped);
		}
		if(($event instanceof SetupBuildingEvent)) {
			$sb = new SetupBlock("Word Filter");
			$sb->add_longtext_option("word_filter");
			$sb->add_label("<br>(each line should be search term and replace term, separated by a comma)");
			$event->panel->add_block($sb);
		}
	}

	private function filter($text) {
		$map = $this->get_map();
		foreach($map as $search => $replace) {
			$search = trim($search);
			$replace = trim($replace);
			$search = "/\\b$search\\b/i";
			$text = preg_replace($search, $replace, $text);
		}
		return $text;
	}

	private function get_map() {
		global $config;
		$raw = $config->get_string("word_filter");
		$lines = explode("\n", $raw);
		$map = array();
		foreach($lines as $line) {
			$parts = split(",", $line);
			if(count($parts) == 2) {
				$map[$parts[0]] = $parts[1];
			}
		}
		return $map;
	}
}
add_event_listener(new WordFilter(), 40); // before emoticon filter
?>
