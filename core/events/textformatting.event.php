<?php
/*
 * TextFormattingEvent:
 *   $original  - for reference
 *   $formatted - with formatting applied
 *   $stripped  - with formatting removed
 *
 */
class TextFormattingEvent extends Event {
	var $original;
	var $formatted;
	var $stripped;

	public function TextFormattingEvent($text) {
		$this->original  = $text;
		$this->formatted = $text;
		$this->stripped  = $text;
	}
}
?>
