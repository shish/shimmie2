<?php

class BulkAddTheme extends Themelet {
	var $messages = array();

	public function display_upload_results($page) {
		$page->set_title("Adding folder");
		$page->set_heading("Adding folder");
		$page->add_block(new NavBlock());
		foreach($this->messages as $block) {
			$page->add_block($block);
		}
	}

	public function display_admin_block($page) {
		$html = "
			Add a folder full of images; any subfolders will have their names
			used as tags for the images within.
			<br>Note: this is the folder as seen by the server -- you need to
			upload via FTP or something first.
			
			<p><form action='".make_link("bulk_add")."' method='POST'>
				Directory to add: <input type='text' name='dir' size='40'>
				<input type='submit' value='Add'>
			</form>
		";
		$page->add_block(new Block("Bulk Add", $html));
	}

	public function add_status($title, $body) {
		$this->messages[] = new Block($title, $body);
	}
}
?>
