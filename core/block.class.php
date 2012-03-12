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

	/**
	 *
	 */
	var $id;

	public function __construct($header, $body, /*string*/ $section="main", /*int*/ $position=50, $id=null) {
		$this->header = $header;
		$this->body = $body;
		$this->section = $section;
		$this->position = $position;
		$this->id = str_replace(' ', '_', is_null($id) ? (is_null($header) ? md5($body) : $header) . $section : $id);
	}

	public function get_html($hidable=false) {
		$h = $this->header;
		$b = $this->body;
		$i = $this->id;
		$html = "<section id='$i'>";
		$h_toggler = $hidable ? " shm-toggler" : "";
		if(!is_null($h)) $html .= "<h3 data-toggle-sel='#$i' class='$h_toggler'>$h</h3>";
		if(!is_null($b)) $html .= "<div class='blockbody'>$b</div>";
		$html .= "</section>";
		return $html;
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
