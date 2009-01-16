<?php
/**
 * Name: Random Image
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Do things with a random image
 * Link: http://trac.shishnet.org/shimmie2/wiki/Contrib/Extensions/RandomImage
 */

class RandomImage implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("random_image")) {
			global $config;
			global $database;

			if($event->count_args() == 1) {
				$action = $event->get_arg(0);
				$search_terms = array();
			}
			else if($event->count_args() == 2) {
				$action = $event->get_arg(0);
				$search_terms = explode(' ', $event->get_arg(1));
			}
			$image = Image::by_random($config, $database, $search_terms);

			if($event->get_arg(0) == "download") {
				if(!is_null($image)) {
					$event->page->set_mode("data");
					$event->page->set_type("image/jpeg");
					$event->page->set_data(file_get_contents($image->get_image_filename()));
				}
			}
			if($event->get_arg(0) == "view") {
				if(!is_null($image)) {
					send_event(new DisplayingImageEvent($image, $event->page));
				}
			}
		}

		if(($event instanceof SetupBuildingEvent)) {
			$sb = new SetupBlock("Random Image");
			$sb->add_bool_option("show_random_block", "Show Random Block: ");
			$event->panel->add_block($sb);
		}

		if($event instanceof PostListBuildingEvent) {
			global $config, $database;
			if($config->get_bool("show_random_block")) {
				$image = Image::by_random($config, $database, $event->search_terms);
				if(!is_null($image)) {
					$this->theme->display_random($event->page, $image);
				}
			}
		}
	}
}
add_event_listener(new RandomImage());
?>
