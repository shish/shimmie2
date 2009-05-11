<?php
/**
 * Name: Random Image
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Do things with a random image
 * Documentation:
 *  <b>Viewing a random image</b>
 *  <br>Visit <code>/random_image/view</code>
 *  <p><b>Downloading a random image</b>
 *  <br>Link to <code>/random_image/download</code>. This will give
 *  the raw data for an image (no HTML). This is useful so that you
 *  can set your desktop wallpaper to be the download URL, refreshed
 *  every couple of hours.
 *  <p><b>Getting a random image from a subset</b>
 *  <br>Adding a slash and some search terms will get a random image
 *  from those results. This can be useful if you want a specific size
 *  of random image, or from a category. You could link to
 *  <code>/random_image/download/size:1024x768+cute</code>
 */

class RandomImage implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("random_image")) {
			if($event->count_args() == 1) {
				$action = $event->get_arg(0);
				$search_terms = array();
			}
			else if($event->count_args() == 2) {
				$action = $event->get_arg(0);
				$search_terms = explode(' ', $event->get_arg(1));
			}
			$image = Image::by_random($search_terms);

			if($event->get_arg(0) == "download") {
				if(!is_null($image)) {
					$page->set_mode("data");
					$page->set_type("image/jpeg");
					$page->set_data(file_get_contents($image->get_image_filename()));
				}
			}
			if($event->get_arg(0) == "view") {
				if(!is_null($image)) {
					send_event(new DisplayingImageEvent($image, $page));
				}
			}
		}

		if(($event instanceof SetupBuildingEvent)) {
			$sb = new SetupBlock("Random Image");
			$sb->add_bool_option("show_random_block", "Show Random Block: ");
			$event->panel->add_block($sb);
		}

		if($event instanceof PostListBuildingEvent) {
			if($config->get_bool("show_random_block")) {
				$image = Image::by_random($event->search_terms);
				if(!is_null($image)) {
					$this->theme->display_random($page, $image);
				}
			}
		}
	}
}
add_event_listener(new RandomImage());
?>
