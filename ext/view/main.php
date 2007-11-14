<?php

class ImageInfoBoxBuildingEvent extends Event {
	var $parts = array();
	var $image;
	var $user;

	public function ImageInfoBoxBuildingEvent($image, $user) {
		$this->image = $image;
		$this->user = $user;
	}

	public function add_part($html, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = $html;
	}
}

class ImageInfoSetEvent extends Event {
	var $image_id;

	public function ImageInfoSetEvent($image_id) {
		$this->image_id = int_escape($image_id);
	}
}

class ViewImage extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("view", "ViewTheme");

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "post") && ($event->get_arg(0) == "view")) {
			$image_id = int_escape($event->get_arg(1));
			
			global $database;
			$image = $database->get_image($image_id);

			if(!is_null($image)) {
				send_event(new DisplayingImageEvent($image, $event->page));
			}
			else {
				$this->theme->display_error($event->page, "Image not found", "No image in the database has the ID #$image_id");
			}
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "post") && ($event->get_arg(0) == "set")) {
			$image_id = int_escape($_POST['image_id']);

			send_event(new ImageInfoSetEvent($image_id));
			
			$query = $_POST['query'];
			$event->page->set_mode("redirect");
			$event->page->set_redirect(make_link("post/view/$image_id", $query));
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			$iibbe = new ImageInfoBoxBuildingEvent($event->get_image(), $event->user);
			send_event($iibbe);
			ksort($iibbe->parts);
			$this->theme->display_page($event->page, $event->get_image(), $iibbe->parts);
		}
	}
}
add_event_listener(new ViewImage());
?>
