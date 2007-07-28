<?php

class AdminPageTheme extends Themelet {
	public function display_page($page) {
		$page->set_title("Admin Tools");
		$page->set_heading("Admin Tools");
		$page->add_block(new NavBlock());
	}

	public function display_delete_block($page, $image_id) {
		$i_image_id = int_escape($image_id);
		$html = "
			<form action='".make_link("admin/delete_image")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='submit' value='Delete'>
			</form>
		";
		$page->add_block(new Block("Admin", $html, "left"));
	}
}
?>
