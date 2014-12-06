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
	/** @var \Image  */
	public $image;
	public $page, $context;

	/**
	 * @param Image $image
	 */
	public function __construct(Image $image) {
		$this->image = $image;
	}

	/**
	 * @return Image
	 */
	public function get_image() {
		return $this->image;
	}
}

class ImageInfoBoxBuildingEvent extends Event {
	/** @var array  */
	public $parts = array();
	/** @var \Image  */
	public $image;
	/** @var \User  */
	public $user;

	/**
	 * @param Image $image
	 * @param User $user
	 */
	public function __construct(Image $image, User $user) {
		$this->image = $image;
		$this->user = $user;
	}

	/**
	 * @param string $html
	 * @param int $position
	 */
	public function add_part($html, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = $html;
	}
}

class ImageInfoSetEvent extends Event {
	/** @var \Image */
	public $image;

	/**
	 * @param Image $image
	 */
	public function __construct(Image $image) {
		$this->image = $image;
	}
}

class ImageAdminBlockBuildingEvent extends Event {
	/** @var array  */
	var $parts = array();
	/** @var \Image|null  */
	public $image = null;
	/** @var null|\User  */
	public $user = null;

	/**
	 * @param Image $image
	 * @param User $user
	 */
	public function __construct(Image $image, User $user) {
		$this->image = $image;
		$this->user = $user;
	}

	/**
	 * @param string $html
	 * @param int $position
	 */
	public function add_part(/*string*/ $html, /*int*/ $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = $html;
	}
}

class ViewImage extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		if($event->page_matches("post/prev") ||	$event->page_matches("post/next")) {
			$image_id = int_escape($event->get_arg(0));

			if(isset($_GET['search'])) {
				$search_terms = explode(' ', $_GET['search']);
				$query = "#search=".url_escape($_GET['search']);
			}
			else {
				$search_terms = array();
				$query = null;
			}

			$image = Image::by_id($image_id);
			if(is_null($image)) {
				$this->theme->display_error(404, "Image not found", "Image $image_id could not be found");
				return;
			}

			if($event->page_matches("post/next")) {
				$image = $image->get_next($search_terms);
			}
			else {
				$image = $image->get_prev($search_terms);
			}

			if(is_null($image)) {
				$this->theme->display_error(404, "Image not found", "No more images");
				return;
			}

			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/{$image->id}", $query));
		}
		else if($event->page_matches("post/view")) {
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
				$this->theme->display_error(404, "Image not found", "No image in the database has the ID #$image_id");
			}
		}
		else if($event->page_matches("post/set")) {
			if(!isset($_POST['image_id'])) return;

			$image_id = int_escape($_POST['image_id']);

			send_event(new ImageInfoSetEvent(Image::by_id($image_id)));

			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id", url_escape(@$_POST['query'])));
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $user;
		$iibbe = new ImageInfoBoxBuildingEvent($event->get_image(), $user);
		send_event($iibbe);
		ksort($iibbe->parts);
		$this->theme->display_page($event->get_image(), $iibbe->parts);
	}
}

