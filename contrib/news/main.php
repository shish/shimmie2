<?php
/**
 * Name: News
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a short amonut of text in a block on the post list
 */

class News implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);
		
		if($event instanceof PostListBuildingEvent) {
			if(strlen($event->context->config->get_string("news_text")) > 0) {
				$this->theme->display_news($event->page, $event->context->config->get_string("news_text"));
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
