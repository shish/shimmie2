<?php
/*
 * ThumbnailGenerationEvent:
 * Request a thumb be made for an image
 */
class ThumbnailGenerationEvent extends Event {
	var $hash;
	var $type;

	public function ThumbnailGenerationEvent($hash, $type) {
		$this->hash = $hash;
		$this->type = $type;
	}
}
?>
