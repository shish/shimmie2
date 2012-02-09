<?php
/**
 * Name: 404 Detector
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Visibility: admin
 * Description: Shows an error message when the user views a page with no content
 */

class Handle404 extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page;
		// hax.
		if($page->mode == "page" && (!isset($page->blocks) || $this->count_main($page->blocks) == 0)) {
			$h_pagename = html_escape(implode('/', $event->args));
			log_debug("handle_404", "Hit 404: $h_pagename");
			
			$page->add_http_header("HTTP/1.0 404 Page Not Found",5);
			$page->set_title("404");
			$page->set_heading("404 - No Handler Found");
			$page->add_block(new NavBlock());
			$page->add_block(new Block("Explanation", "No handler could be found for the page '$h_pagename'"));
		}
	}

	private function count_main($blocks) {
		$n = 0;
		foreach($blocks as $block) {
			if($block->section == "main") $n++; // more hax.
		}
		return $n;
	}

	public function get_priority() {return 99;}
}
?>
