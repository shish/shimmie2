<?php
/*
 * Name: Generic Blocks
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Add HTML to some space
 * Documentation:
 *  Any HTML is allowed
 *  <br>Separate different blocks with a line of 4 dashes
 *  <br>Within each block, some settings can be set.
 *  <br>Example settings
 *  <pre>
 *  Title: some text
 *  Area: main
 *  Priority: 100
 *  Pages: *
 *  
 *  Here is some &lt;b&gt;html&lt;/b&gt;
 *  ----
 *  Title: another block, on the left this time
 *  Priority: 0
 *  Pages: post/view/*
 *  
 *  Area can be "left" or "main" in the default theme
 *  other themes may have more areas. Priority has 0
 *  near the top of the screen and 100 near the bottom
 *  </pre>
 */

class Blocks extends SimpleExtension {
	public function onPageRequest($event) {
		global $config, $page;
		$all = $config->get_string("blocks_text");
		$blocks = explode("----", $all);
		foreach($blocks as $block) {
			$title = "";
			$text = "";
			$area = "left";
			$pri = 50;
			$pages = "*";

			$lines = explode("\n", $block);
			foreach($lines as $line) {
				if(strpos($line, ":")) {
					$parts = explode(":", $line, 2);
					$parts[0] = trim($parts[0]);
					$parts[1] = trim($parts[1]);
					if($parts[0] == "Title") {
						$title = $parts[1];
						continue;
					}
					if($parts[0] == "Area") {
						$area = $parts[1];
						continue;
					}
					if($parts[0] == "Priority") {
						$pri = (int)$parts[1];
						continue;
					}
					if($parts[0] == "Pages") {
						$pages = $parts[1];
						continue;
					}
				}
				$text = $text . "\n" . $line;
			}
			if(fnmatch($pages, implode("/", $event->args))) {
				$page->add_block(new Block($title, $text, $area, $pri));
			}
		}
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Blocks");
		$sb->add_label("See <a href='".make_link("ext_doc/blocks")."'>the docs</a> for formatting");
		$sb->add_longtext_option("blocks_text");
		$event->panel->add_block($sb);
	}
}
?>
