<?php
/**
 * A basic chunk of a page
 */
class Block {
	/**
	 * The block's title
	 *
	 * @retval string
	 */
	var $header;
	/**
	 * The content
	 *
	 * @retval string
	 */
	var $body;
	/** 
	 * Where the block should be placed. The default theme supports
	 * "main" and "left", other themes can add their own areas
	 *
	 * @retval string
	 */
	var $section;
	/**
	 * How far down the section the block should appear, higher
	 * numbers appear lower. The scale is 0-100 by convention,
	 * though any number or string will work.
	 *
	 * @retval int
	 */
	var $position;

	public function __construct($header, $body, $section="main", $position=50) {
		$this->header = $header;
		$this->body = $body;
		$this->section = $section;
		$this->position = $position;
	}
}


/**
 * A generic navigation block with a link to the main page.
 *
 * Used because "new NavBlock()" is easier than "new Block('Navigation', ..."
 */
class NavBlock extends Block {
	public function __construct() {
		parent::__construct("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0);
	}
}
?>
