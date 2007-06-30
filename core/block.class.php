<?php
class Block {
	var $header;
	var $body;
	var $section;
	var $position;

	public function Block($header, $body, $section="main", $position=50) {
		$this->header = $header;
		$this->body = $body;
		$this->section = $section;
		$this->position = $position;
	}
}
?>
