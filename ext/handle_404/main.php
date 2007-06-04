<?php

class Handle404 extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent')) {
			global $page;
			// hax.
			if($page->mode == "page" && (!isset($page->mainblocks) || count($page->mainblocks) == 0)) {
				$h_pagename = html_escape($event->page);
				header("HTTP/1.0 404 Page Not Found");
				$page->set_title("404");
				$page->set_heading("404 - No Handler Found");
				$page->add_main_block(new Block("Explanation", "No handler could be found for the page '$h_pagename'"));
			}
		}
	}
}
add_event_listener(new Handle404(), 99);
?>
