<?php
/*
 * UploadingImageEvent:
 *   $image_id
 *
 * An image is being uploaded.
 */
class UploadingImageEvent extends Event {
	var $image;
	var $user;

	public function UploadingImageEvent($image, $user) {
		$this->image = $image;
		$this->user = $user;
	}
}
?>
