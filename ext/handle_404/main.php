<?php

class Handle404 extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent')) {
			$page = $event->page;
			// hax.
			if($page->mode == "page" && (!isset($page->blocks) || $this->count_main($page->blocks) == 0)) {
				$h_pagename = html_escape($event->page);
				header("HTTP/1.0 404 Page Not Found");
				$page->set_title("404");
				$page->set_heading("404 - No Handler Found");
				$page->add_block(new NavBlock());
				$page->add_block(new Block("Explanation", "No handler could be found for the page '$h_pagename'"));
			}
		}
	}

	private function count_main($blocks) {
		$n = 0;
		foreach($blocks as $block) {
			if($block->section == "main") $n++; // more hax.
		}
		return $n;
	}
}
add_event_listener(new Handle404(), 99); // hax++
?>
