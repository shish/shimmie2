<?php

class News extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("news", "NewsTheme");
		
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "index")) {
			global $config;
			if(strlen($config->get_string("news_text")) > 0) {
				$this->theme->display_news($event->page, $config->get_string("news_text"));
			}
		}
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("News");
			$sb->add_longtext_option("news_text");
			$event->panel->add_block($sb);
		}
	}
}
add_event_listener(new News());
?>
