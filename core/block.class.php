<?php

/**
 * Class Block
 *
 * A basic chunk of a page.
 */
class Block {
	/**
	 * The block's title.
	 *
	 * @var string
	 */
	public $header;

	/**
	 * The content of the block.
	 *
	 * @var string
	 */
	public $body;

	/**
	 * Where the block should be placed. The default theme supports
	 * "main" and "left", other themes can add their own areas.
	 *
	 * @var string
	 */
	public $section;

	/**
	 * How far down the section the block should appear, higher
	 * numbers appear lower. The scale is 0-100 by convention,
	 * though any number or string will work.
	 *
	 * @var int
	 */
	public $position;

	/**
	 * A unique ID for the block.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Construct a block.
	 *
	 * @param string $header
	 * @param string $body
	 * @param string $section
	 * @param int $position
	 * @param null|int $id A unique ID for the block (generated automatically if null).
	 */
	public function __construct($header, $body, /*string*/ $section="main", /*int*/ $position=50, $id=null) {
		$this->header = $header;
		$this->body = $body;
		$this->section = $section;
		$this->position = $position;
		$this->id = preg_replace('/[^\w]/', '',str_replace(' ', '_', is_null($id) ? (is_null($header) ? md5($body) : $header) . $section : $id));
	}

	/**
	 * Get the HTML for this block.
	 *
	 * @param bool $hidable
	 * @return string
	 */
	public function get_html($hidable=false) {
		$h = $this->header;
		$b = $this->body;
		$i = $this->id;
		$html = "<section id='$i'>";
		$h_toggler = $hidable ? " shm-toggler" : "";
		if(!empty($h)) $html .= "<h3 data-toggle-sel='#$i' class='$h_toggler'>$h</h3>";
		if(!empty($b)) $html .= "<div class='blockbody'>$b</div>";
		$html .= "</section>\n";
		return $html;
	}
}


/**
 * Class NavBlock
 *
 * A generic navigation block with a link to the main page.
 *
 * Used because "new NavBlock()" is easier than "new Block('Navigation', ..."
 *
 */
class NavBlock extends Block {
	public function __construct() {
		parent::__construct("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0);
	}
}
