<?php

class NewsTheme extends Themelet {
	public function display_news($page, $text) {
		$page->add_block(new Block("Note", $text, "left", 5));
	}
}
?>
