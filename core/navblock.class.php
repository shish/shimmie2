<?php
class NavBlock {
	var $header;
	var $body;

	public function NavBlock() {
		$this->header = "Navigation";
		$this->body = "<a href='".make_link("index")."'>Index</a>";
	}
}
?>
