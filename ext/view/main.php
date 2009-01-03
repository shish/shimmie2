<?php
/*
 * DisplayingImageEvent:
 *   $image -- the image being displayed
 *   $page  -- the page to display on
 *
 * Sent when an image is ready to display. Extensions who
 * wish to appear on the "view" page should listen for this,
 * which only appears when an image actually exists.
 */
class DisplayingImageEvent extends Event {
	var $image, $page;

	public function DisplayingImageEvent($image, $page) {
		$this->image = $image;
		$this->page = $page;
	}

	public function get_image() {
		return $this->image;
	}
}

class ImageInfoBoxBuildingEvent extends Event {
	var $parts = array();
	var $image;
	var $user;

	public function ImageInfoBoxBuildingEvent(Image $image, User $user) {
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

class ImageAdminBlockBuildingEvent extends Event {
	var $parts = array();
	var $image = null;
	var $user = null;

	public function ImageAdminBlockBuildingEvent(Image $image, User $user) {
		$this->image = $image;
		$this->user = $user;
	}

	public function add_part($html, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = $html;
	}
}
class ViewImage implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("post/view")) {
			$image_id = int_escape($event->get_arg(0));
			
			global $database;
			global $config;
			$image = Image::by_id($config, $database, $image_id);

			if(!is_null($image)) {
				send_event(new DisplayingImageEvent($image, $event->page));
				$iabbe = new ImageAdminBlockBuildingEvent($image, $event->user);
				send_event($iabbe);
				ksort($iabbe->parts);
				$this->theme->display_admin_block($event->page, $iabbe->parts);
			}
			else {
				$this->theme->display_error($event->page, "Image not found", "No image in the database has the ID #$image_id");
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("post/set")) {
			$image_id = int_escape($_POST['image_id']);

			send_event(new ImageInfoSetEvent($image_id));
			
			$query = $_POST['query'];
			$event->page->set_mode("redirect");
			$event->page->set_redirect(make_link("post/view/$image_id", $query));
		}

		if($event instanceof DisplayingImageEvent) {
			global $user;
			$iibbe = new ImageInfoBoxBuildingEvent($event->get_image(), $user);
			send_event($iibbe);
			ksort($iibbe->parts);
			$this->theme->display_page($event->page, $event->get_image(), $iibbe->parts);
		}
	}
}
add_event_listener(new ViewImage());
?>
