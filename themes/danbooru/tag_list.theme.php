<?php

class CustomTagListTheme extends TagListTheme {
	public function display_page($page) {
		$page->disable_left();
		parent::display_page($page);
	}
}
?>
