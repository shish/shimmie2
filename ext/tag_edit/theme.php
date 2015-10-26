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
			<table class='form'>
				<tr><th>Search</th><td><input type='text' name='search' class='autocomplete_tags' autocomplete='off'></tr>
				<tr><th>Replace</th><td><input type='text' name='replace' class='autocomplete_tags' autocomplete='off'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Replace'></td></tr>
			</table>
		</form>
		";
		$page->add_block(new Block("Mass Tag Edit", $html));
	}

	public function mss_html($terms) {
		$h_terms = html_escape($terms);
		$html = make_form(make_link("tag_edit/mass_source_set"), "POST") . "
				<input type='hidden' name='tags' value='$h_terms'>
				<input type='text' name='source' value=''>
				<input type='submit' value='Set Source For All' onclick='return confirm(\"This will mass-edit all sources on the page.\nAre you sure you want to do this?\")'>
			</form>
		";
		return $html;
	}

	public function get_tag_editor_html(Image $image) {
		global $user;

		$tag_links = array();
		foreach($image->get_tag_array() as $tag) {
			$h_tag = html_escape($tag);
			$u_tag = url_escape($tag);
			$h_link = make_link("post/list/$u_tag/1");
			$tag_links[] = "<a href='$h_link'>$h_tag</a>";
		}
		$h_tag_links = implode(" ", $tag_links);
		$h_tags = html_escape($image->get_tag_list());

		return "
			<tr>
				<th width='50px'>Tags</th>
				<td>
		".($user->can("edit_image_tag") ? "
					<span class='view'>$h_tag_links</span>
					<input class='edit autocomplete_tags' type='text' name='tag_edit__tags' value='$h_tags' id='tag_editor' autocomplete='off'>
		" : "
					$h_tag_links
		")."
				</td>
			</tr>
		";
	}

	public function get_user_editor_html(Image $image) {
		global $user;
		$h_owner = html_escape($image->get_owner()->name);
		$h_av = $image->get_owner()->get_avatar_html();
		$h_date = autodate($image->posted);
		$h_ip = $user->can("view_ip") ? " (".show_ip($image->owner_ip, "Image posted {$image->posted}").")" : "";
		return "
			<tr>
				<th>Uploader</th>
				<td>
		".($user->can("edit_image_owner") ? "
					<span class='view'><a class='username' href='".make_link("user/$h_owner")."'>$h_owner</a>$h_ip, $h_date</span>
					<input class='edit' type='text' name='tag_edit__owner' value='$h_owner'>
		" : "
					<a class='username' href='".make_link("user/$h_owner")."'>$h_owner</a>$h_ip, $h_date
		")."
				</td>
				<td width='80px' rowspan='4'>$h_av</td>
			</tr>
		";
	}

	public function get_source_editor_html(Image $image) {
		global $user;
		$h_source = html_escape($image->get_source());
		$f_source = $this->format_source($image->get_source());
		$style = "overflow: hidden; white-space: nowrap; max-width: 350px; text-overflow: ellipsis;";
		return "
			<tr>
				<th>Source</th>
				<td>
		".($user->can("edit_image_source") ? "
					<div class='view' style='$style'>$f_source</div>
					<input class='edit' type='text' name='tag_edit__source' value='$h_source'>
		" : "
					<div style='$style'>$f_source</div>
		")."
				</td>
			</tr>
		";
	}

	/**
	 * @param string $source
	 * @return string
	 */
	protected function format_source(/*string*/ $source) {
		if(!empty($source)) {
			if(!startsWith($source, "http://") && !startsWith($source, "https://")) {
				$source = "http://" . $source;
			}
			$proto_domain = explode("://", $source);
			$h_source = html_escape($proto_domain[1]);
			$u_source = html_escape($source);
			if(endsWith($h_source, "/")) {
				$h_source = substr($h_source, 0, -1);
			}
			return "<a href='$u_source'>$h_source</a>";
		}
		return "Unknown";
	}

	public function get_lock_editor_html(Image $image) {
		global $user;
		$b_locked = $image->is_locked() ? "Yes (Only admins may edit these details)" : "No";
		$h_locked = $image->is_locked() ? " checked" : "";
		return "
			<tr>
				<th>Locked</th>
				<td>
		".($user->can("edit_image_lock") ? "
					<span class='view'>$b_locked</span>
					<input class='edit' type='checkbox' name='tag_edit__locked'$h_locked>
		" : "
					$b_locked
		")."
				</td>
			</tr>
		";
	}
}

