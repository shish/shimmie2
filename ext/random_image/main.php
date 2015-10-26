<?php
/*
 * Name: Random Image
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
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
 *  <code>/random_image/download/size=1024x768+cute</code>
 */

class RandomImage extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page;

		if($event->page_matches("random_image")) {
			if($event->count_args() == 1) {
				$action = $event->get_arg(0);
				$search_terms = array();
			}
			else if($event->count_args() == 2) {
				$action = $event->get_arg(0);
				$search_terms = explode(' ', $event->get_arg(1));
			}
			else {
				throw new SCoreException("Error: too many arguments.");
			}
			$image = Image::by_random($search_terms);

			if($action === "download") {
				if(!is_null($image)) {
					$page->set_mode("data");
					$page->set_type($image->get_mime_type());
					$page->set_data(file_get_contents($image->get_image_filename()));
				}
			}
			else if($action === "view") {
				if(!is_null($image)) {
					send_event(new DisplayingImageEvent($image, $page));
				}
			}
			else if($action === "widget") {
				if(!is_null($image)) {
					$page->set_mode("data");
					$page->set_type("text/html");
					$page->set_data($this->theme->build_thumb_html($image));
				}
			}
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Random Image");
		$sb->add_bool_option("show_random_block", "Show Random Block: ");
		$event->panel->add_block($sb);
	}

	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $page;
		if($config->get_bool("show_random_block")) {
			$image = Image::by_random($event->search_terms);
			if(!is_null($image)) {
				$this->theme->display_random($page, $image);
			}
		}
	}
}

