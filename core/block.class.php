<?php
/*
 * A basic chunk of page
 *  $header -- the block's title
 *  $body   -- the content
 *  $section -- where the block should be placed. The default theme supports
 *              "main" and "left", other themes can add their own areas
 *  $position -- how far down the section the block should appear, higher
 *               numbers appear lower. The scale is 0-100 by convention,
 *               though any number or string will work.
 */
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


/*
 * A generic navigation block with a link to the main page. Used
 * because "new NavBlock()" is easier than "new Block('Navigation', ..."
 */
class NavBlock extends Block {
	public function NavBlock() {
		$this->header = "Navigation";
		$this->body = "<a href='".make_link()."'>Index</a>";
		$this->section = "left";
		$this->position = 0;
	}
}
?>
