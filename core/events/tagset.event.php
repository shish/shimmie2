<?php
/*
 * TagSetEvent:
 *   $image_id
 *   $tags
 *
 */
class TagSetEvent extends Event {
	var $image_id;
	var $tags;

	public function TagSetEvent($image_id, $tags) {
		$this->image_id = $image_id;
		$this->tags = $tags;
	}
}
?>
