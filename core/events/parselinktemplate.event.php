<?php
class ParseLinkTemplateEvent extends Event {
	var $link;
	var $original;
	var $image;

	public function ParseLinkTemplateEvent($link, $image) {
		$this->link = $link;
		$this->original = $link;
		$this->image = $image;
	}

	public function replace($needle, $replace) {
		$this->link = str_replace($needle, $replace, $this->link);
	}
}
?>
