<?php

class AliasEditorTheme extends Themelet {
	/**
	 * Show a page of aliases.
	 *
	 * Note: $can_manage = whether things like "add new alias" should be shown
	 *
	 * @param array $aliases An array of ($old_tag => $new_tag)
	 * @param int $pageNumber
	 * @param int $totalPages
	 */
	public function display_aliases($aliases, $pageNumber, $totalPages) {
		global $page, $user;

		$can_manage = $user->can("manage_alias_list");
		if($can_manage) {
			$h_action = "<th width='10%'>Action</th>";
			$h_add = "
				<tr>
					".make_form(make_link("alias/add"))."
						<td><input type='text' name='oldtag' class='autocomplete_tags' autocomplete='off'></td>
						<td><input type='text' name='newtag' class='autocomplete_tags' autocomplete='off'></td>
						<td><input type='submit' value='Add'></td>
					</form>
				</tr>
			";
		}
		else {
			$h_action = "";
			$h_add = "";
		}

		$h_aliases = "";
		foreach($aliases as $old => $new) {
			$h_old = html_escape($old);
			$h_new = "<a href='".make_link("post/list/".url_escape($new)."/1")."'>".html_escape($new)."</a>";

			$h_aliases .= "<tr><td>$h_old</td><td>$h_new</td>";
			if($can_manage) {
				$h_aliases .= "
					<td>
						".make_form(make_link("alias/remove"))."
							<input type='hidden' name='oldtag' value='$h_old'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				";
			}
			$h_aliases .= "</tr>";
		}
		$html = "
			<table id='aliases' class='sortable zebra'>
				<thead><tr><th>From</th><th>To</th>$h_action</tr></thead>
				<tbody>$h_aliases</tbody>
				<tfoot>$h_add</tfoot>
			</table>
			<p><a href='".make_link("alias/export/aliases.csv")."' download='aliases.csv'>Download as CSV</a></p>
		";

		$bulk_html = "
			".make_form(make_link("alias/import"), 'post', true)."
				<input type='file' name='alias_file'>
				<input type='submit' value='Upload List'>
			</form>
		";

		$page->set_title("Alias List");
		$page->set_heading("Alias List");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Aliases", $html));
		if($can_manage) {
			$page->add_block(new Block("Bulk Upload", $bulk_html, "main", 51));
		}

		$this->display_paginator($page, "alias/list", null, $pageNumber, $totalPages);
	}
}

