<?php

class CustomTagListTheme extends TagListTheme {
	public function display_page(Page $page) {
		$page->disable_left();
		parent::display_page($page);
	}
}

