<?php

class Handle404 implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof PageRequestEvent) {
			$page = $event->page;
			// hax.
			if($page->mode == "page" && (!isset($page->blocks) || $this->count_main($page->blocks) == 0)) {
				$h_pagename = html_escape($event->page_name);
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
