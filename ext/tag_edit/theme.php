<?php

class TagEditTheme extends Themelet {
	/*
	 * Display a form to edit tags for $image
	 * The form should link to tag_edit/set, with the following set:
	 *   POST[image_id]
	 *   POST[query] = when redirecting to post/view, what the search string should be set to
	 *   POST[tags]
	 *
	 * Note $image->get_tag_list()
	 */
	public function display_editor($page, $image) {
		global $database;
		
		if(isset($_GET['search'])) {
			$h_query = "search=".url_escape($_GET['search']);
		}
		else {
			$h_query = "";
		}

		$h_tags = html_escape($image->get_tag_list());
		$i_image_id = int_escape($image->id);

		$html = "
		<p><form action='".make_link("tag_edit/set")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='query' value='$h_query'>
			<input type='text' size='50' name='tags' value='$h_tags'>
			<input type='submit' value='Set'>
		</form>
		";
		
		$page->add_block(new Block(null, $html, "main", 5));
	}

	/*
	 * Display a form which links to tag_edit/replace with POST[search]
	 * and POST[replace] set appropriately
	 */
	public function display_mass_editor($page) {
		$html = "
		<form action='".make_link("tag_edit/replace")."' method='POST'>
			<table border='1' style='width: 200px;'>
				<tr><td>Search</td><td><input type='text' name='search'></tr>
				<tr><td>Replace</td><td><input type='text' name='replace'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Replace'></td></tr>
			</table>
		</form>
		";
		$page->add_block(new Block("Mass Tag Edit", $html));
	}
}
?>
