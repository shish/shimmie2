<?php

class News extends Extension {
	public function receive_event($event) {
		global $page;
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			global $config;
			if(strlen($config->get_string("news_text")) > 0) {
				$page->add_side_block(new Block("Note", $config->get_string("news_text")), 5);
			}
		}
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("News");
			$sb->add_longtext_option("news_text");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_string("news_text", $_POST['news_text']);
		}
	}
}
add_event_listener(new News());
?>
