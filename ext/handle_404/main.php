<?php
/**
 * Name: 404 Detector
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Visibility: admin
 * Description: If Shimmie can't handle a request, check static files; if that fails, show a 404
 */

class Handle404 extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page;
		// hax.
		if($page->mode == "page" && (!isset($page->blocks) || $this->count_main($page->blocks) == 0)) {
			$h_pagename = html_escape(implode('/', $event->args));
			$f_pagename = preg_replace("/[^a-z_\-\.]+/", "_", $h_pagename);
			$theme_name = $config->get_string("theme", "default");

			if(file_exists("themes/$theme_name/$f_pagename") || file_exists("lib/static/$f_pagename")) {
				$filename = file_exists("themes/$theme_name/$f_pagename") ?
						"themes/$theme_name/$f_pagename" : "lib/static/$f_pagename";

				$page->add_http_header("Cache-control: public, max-age=600");
				$page->add_http_header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 600) . ' GMT');
				$page->set_mode("data");
				$page->set_data(file_get_contents($filename));
				if(endsWith($filename, ".ico")) $page->set_type("image/x-icon");
				if(endsWith($filename, ".png")) $page->set_type("image/png");
				if(endsWith($filename, ".txt")) $page->set_type("text/plain");
			}
			else {
				log_debug("handle_404", "Hit 404: $h_pagename");
				$page->set_code(404);
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
		if(ext_is_live("Chatbox")) {
			$n--; // even more hax.
		}
		return $n;
	}

	public function get_priority() {return 99;}
}

