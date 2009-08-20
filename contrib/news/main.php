<?php
/*
 * Name: News
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a short amount of text in a block on the post list
 * Documentation:
 *  Any HTML is allowed
 */

class News extends SimpleExtension {
	public function onPostListBuilding($event) {
		global $config, $page;
		if(strlen($config->get_string("news_text")) > 0) {
			$this->theme->display_news($page, $config->get_string("news_text"));
		}
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("News");
		$sb->add_longtext_option("news_text");
		$event->panel->add_block($sb);
	}
}
?>
