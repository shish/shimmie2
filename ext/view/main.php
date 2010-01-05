<?php
/*
 * Name: Image Viewer
 * Author: Shish
 * Description: Allows users to see uploaded images
 */

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
	var $image, $page, $context;

	public function __construct(Image $image) {
		$this->image = $image;
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
	var $image;

	public function ImageInfoSetEvent($image) {
		$this->image = $image;
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
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(is_a($event, 'PageRequestEvent') && (
			$event->page_matches("post/prev") ||
			$event->page_matches("post/next")
		)) {
			$image_id = int_escape($event->get_arg(0));

			if(isset($_GET['search'])) {
				$search_terms = explode(' ', $_GET['search']);
				$query = "search=".url_escape($_GET['search']);
			}
			else {
				$search_terms = array();
				$query = null;
			}

			$image = Image::by_id($image_id);
			if($event->page_matches("post/next")) {
				$image = $image->get_next($search_terms);
			}
			else {
				$image = $image->get_prev($search_terms);
			}

			if(!is_null($image)) {
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/view/{$image->id}", $query));
			}
			else {
				$this->theme->display_error($page, "Image not found", "No more images");
			}
		}
			
		if(($event instanceof PageRequestEvent) && $event->page_matches("post/view")) {
			$image_id = int_escape($event->get_arg(0));

			$image = Image::by_id($image_id);

			if(!is_null($image)) {
				send_event(new DisplayingImageEvent($image));
				$iabbe = new ImageAdminBlockBuildingEvent($image, $user);
				send_event($iabbe);
				ksort($iabbe->parts);
				$this->theme->display_admin_block($page, $iabbe->parts);
			}
			else {
				$this->theme->display_error($page, "Image not found", "No image in the database has the ID #$image_id");
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("post/set")) {
			$image_id = int_escape($_POST['image_id']);

			send_event(new ImageInfoSetEvent(Image::by_id($image_id)));

			$query = $_POST['query'];
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id", $query));
		}

		if($event instanceof DisplayingImageEvent) {
			$iibbe = new ImageInfoBoxBuildingEvent($event->get_image(), $user);
			send_event($iibbe);
			ksort($iibbe->parts);
			$this->theme->display_page($page, $event->get_image(), $iibbe->parts);
		}
	}
}
add_event_listener(new ViewImage());
?>
