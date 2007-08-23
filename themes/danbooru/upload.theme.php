<?php

class CustomUploadTheme extends UploadTheme {
	public function display_block($page) {
		// this theme links to /upload
		// $page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
	}

	public function display_page($page) {
		$page->disable_left();
		parent::display_page($page);
	}
}
?>
