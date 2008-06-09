<?php
/**
 * Name: Random Image
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Do things with a random image
 * Link: http://trac.shishnet.org/shimmie2/wiki/Contrib/Extensions/RandomImage
 */

class RandomImage extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "random_image")) {
			global $database;
			
			if($event->count_args() == 1) {
				$action = $event->get_arg(0);
				$search_terms = array();
			}
			else if($event->count_args() == 2) {
				$action = $event->get_arg(0);
				$search_terms = explode(' ', $event->get_arg(1));
			}
			$image = $database->get_random_image($search_terms);

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
	}
}
add_event_listener(new RandomImage());
?>
