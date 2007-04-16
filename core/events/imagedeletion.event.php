<?php
/*
 * ImageDeletionEvent:
 *   $image_id
 *
 * An image is being deleted. Used by things like tags
 * and comments handlers to clean out related rows in
 * their tables
 */
class ImageDeletionEvent extends Event {
	var $image;

	public function ImageDeletionEvent($image) {
		$this->image = $image;
	}
}
?>
