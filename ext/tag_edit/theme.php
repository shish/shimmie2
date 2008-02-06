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

	public function get_editor_html($image, $user) {
		$html = "";


		global $config;
		if($config->get_bool("tag_edit_anon") || !$user->is_anonymous()) {
			$h_tags = html_escape($image->get_tag_list());
			$h_source = html_escape($image->get_source());
			$i_image_id = int_escape($image->id);

			$source_edit = "";
			if($config->get_bool("source_edit_anon") || !$user->is_anonymous()) {
				$source_edit = "<tr><td>Source</td><td><input type='text' name='tag_edit__source' value='$h_source'></td></tr>";
			}

			$html .= "
				<table style='width: 500px;'>
				<tr><td width='50px'>Tags</td><td width='300px'><input type='text' name='tag_edit__tags' value='$h_tags'></td></tr>
				$source_edit
				</table>
			";
		}

		return $html;
	}
}
?>
