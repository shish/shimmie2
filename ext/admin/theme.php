<?php

class AdminPageTheme extends Themelet {
	/*
	 * Show the basics of a page, for other extensions to add to
	 */
	public function display_page($page) {
		$page->set_title("Admin Tools");
		$page->set_heading("Admin Tools");
		$page->add_block(new NavBlock());
	}

	/*
	 * Display a link to delete an image
	 *
	 * $image_id = the image to delete
	 */
	public function display_deleter($page, $image_id) {
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
