<?php
/*
 * ImageAdditionEvent:
 *
 * An image is being added to the database
 */
class ImageAdditionEvent extends Event {
	var $image;
	var $user;

	public function ImageAdditionEvent($user, $image) {
		$this->image = $image;
		$this->user = $user;
	}
}
?>
