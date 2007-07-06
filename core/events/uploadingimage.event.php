<?php
/*
 * UploadingImageEvent:
 *   $image_id
 *
 * An image is being uploaded.
 */
class UploadingImageEvent extends Event {
	var $image;
	var $ok;

	public function UploadingImageEvent($image) {
		$this->image = $image;
		$this->ok = false;
	}
}
?>
