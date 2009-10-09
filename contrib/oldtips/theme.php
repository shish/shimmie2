<?php

class TipsTheme extends Themelet {
	public function display_tip($text) {
		global $page;
		$page->add_block(new Block(null, $text, "main", 5));
	}
}
?>
