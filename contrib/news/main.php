<?php
/**
 * Name: News
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a short amount of text in a block on the post list
 * Documentation:
 *  Any HTML is allowed
 */

class News implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof PostListBuildingEvent) {
			if(strlen($config->get_string("news_text")) > 0) {
				$this->theme->display_news($page, $config->get_string("news_text"));
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("News");
			$sb->add_longtext_option("news_text");
			$event->panel->add_block($sb);
		}
	}
}
add_event_listener(new News());
?>
