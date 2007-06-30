<?php
class NavBlock extends Block {
	public function NavBlock() {
		$this->header = "Navigation";
		$this->body = "<a href='".make_link("index")."'>Index</a>";
		$this->section = "left";
		$this->position = 0;
	}
}
?>
