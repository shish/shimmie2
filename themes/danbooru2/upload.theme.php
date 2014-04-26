<?php

class CustomUploadTheme extends UploadTheme {
	public function display_block(Page $page) {
		// this theme links to /upload
		// $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
	}

	public function display_page(Page $page) {
		$page->disable_left();
		parent::display_page($page);
	}
}

