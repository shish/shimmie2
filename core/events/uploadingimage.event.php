<?php
/*
 * UploadingImageEvent:
 *   $image_id
 *
 * An image is being uploaded.
 */
class UploadingImageEvent extends Event {
	var $image;

	public function UploadingImageEvent($image) {
		$this->image = $image;
	}
}
?>
