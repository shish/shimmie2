<?php

class TagEditTheme extends Themelet {
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

	public function get_tag_editor_html($image) {
		$h_tags = html_escape($image->get_tag_list());
		return "<tr><td width='50px'>Tags</td><td width='300px'><input type='text' name='tag_edit__tags' value='$h_tags'></td></tr>";
	}

	public function get_source_editor_html($image) {
		$h_source = html_escape($image->get_source());
		return "<tr><td>Source</td><td><input type='text' name='tag_edit__source' value='$h_source'></td></tr>";
	}
}
?>
