<?php
/*
 * DisplayingImageEvent:
 *   $image
 *
 * Sent when an image is ready to display. Extensions who
 * wish to appear on the "view" page should listen for this,
 * which only appears when an image actually exists.
 */
class DisplayingImageEvent extends Event {
	var $image;
	var $page;

	public function DisplayingImageEvent($image, $page) {
		$this->image = $image;
		$this->page = $page;
	}

	public function get_image() {
		return $this->image;
	}
}
?>
