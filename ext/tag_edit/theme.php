<?php

class TagEditTheme extends Themelet {
	/*
	 * Display a form which links to tag_edit/replace with POST[search]
	 * and POST[replace] set appropriately
	 */
	public function display_mass_editor() {
		global $page;
		$html = "
		".make_form(make_link("tag_edit/replace"))."
			<table style='width: 300px;'>
				<tr><td>Search</td><td><input type='text' name='search'></tr>
				<tr><td>Replace</td><td><input type='text' name='replace'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Replace'></td></tr>
			</table>
		</form>
		";
		$page->add_block(new Block("Mass Tag Edit", $html));
	}

	public function get_tag_editor_html(Image $image) {
		$h_tags = html_escape($image->get_tag_list());
		return "
			<tr>
				<td width='50px'>Tags</td>
				<td width='300px'>
					<!--<span class='view'>$h_tags</span>-->
					<input type='text' name='tag_edit__tags' value='$h_tags' class='autocomplete_tags' id='tag_editor' onfocus='$(\".view\").hide(); $(\".edit\").show();'>
				</td>
			</tr>
		";
	}

	public function get_user_editor_html(Image $image) {
		global $user;
		$h_owner = html_escape($image->get_owner()->name);
		$h_av = $image->get_owner()->get_avatar_html();
		$h_date = autodate($image->posted);
		$ip = $user->can("view_ip") ? " (".show_ip($image->owner_ip, "Image posted {$image->posted}").")" : "";
		return "
			<tr>
				<td>Uploader</td>
				<td>
					<span class='view'><a class='username' href='".make_link("user/$h_owner")."'>$h_owner</a>$ip, $h_date</span>
					<input class='edit' type='text' name='tag_edit__owner' value='$h_owner'>
				</td>
				<td width='80px' rowspan='4'>$h_av</td>
			</tr>
		";
	}

	public function get_source_editor_html(Image $image) {
		$h_source = html_escape($image->get_source());
		$f_source = $this->format_source($image->get_source());
		return "
			<tr>
				<td>Source</td>
				<td>
					<span class='view' style='overflow: hidden; white-space: nowrap;'>$f_source</span>
					<input class='edit' type='text' name='tag_edit__source' value='$h_source'>
				</td>
			</tr>
		";
	}

	private function format_source($source) {
		if(!empty($source)) {
			$h_source = html_escape($source);
			if(startsWith($source, "http://") || startsWith($source, "https://")) {
				return "<a href='$h_source'>$h_source</a>";
			}
			else {
				return "<a href='http://$h_source'>$h_source</a>";
			}
		}
		return "Unknown";
	}

	public function get_lock_editor_html(Image $image) {
		$b_locked = $image->is_locked() ? "Yes (Only admins may edit these details)" : "No";
		$h_locked = $image->is_locked() ? " checked" : "";
		return "
			<tr>
				<td>Locked</td>
				<td>
					<span class='view'>$b_locked</span>
					<input class='edit' type='checkbox' name='tag_edit__locked'$h_locked>
				</td>
			</tr>
		";
	}
}
?>
