<?php

class CustomAdminPageTheme extends AdminPageTheme {
	public function display_page() {
		global $page;
		$page->disable_left();
		parent::display_page();
	}
}


