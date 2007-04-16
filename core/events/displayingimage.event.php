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

	public function DisplayingImageEvent($image) {
		$this->image = $image;
	}

	public function get_image() {
		return $this->image;
	}
}
?>
