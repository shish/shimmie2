<?php
/*
 * SourceSetEvent:
 *   $image_id
 *   $source
 *
 */
class SourceSetEvent extends Event {
	var $image_id;
	var $source;

	public function SourceSetEvent($image_id, $source) {
		$this->image_id = $image_id;
		$this->source = $source;
	}
}
?>
