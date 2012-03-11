<?php

class AdminPageTheme extends Themelet {
	/*
	 * Show the basics of a page, for other extensions to add to
	 */
	public function display_page() {
		global $page;

		$page->set_title("Admin Tools");
		$page->set_heading("Admin Tools");
		$page->add_block(new NavBlock());
	}

	protected function button(/*string*/ $name, /*string*/ $action, /*boolean*/ $protected=false) {
		$c_protected = $protected ? " protected" : "";
		$html = make_form(make_link("admin/$action"), "POST", false, false, false, "admin$c_protected");
		if($protected) {
			$html .= "<input type='checkbox' onclick='$(\"#$action\").attr(\"disabled\", !$(this).is(\":checked\"))'>";
			$html .= "<input type='submit' id='$action' value='$name' disabled='true'>";
		}
		else {
			$html .= "<input type='submit' id='$action' value='$name'>";
		}
		$html .= "</form>\n";
		return $html;
	}

	/*
	 * Show a form which links to admin_utils with POST[action] set to one of:
	 *  'lowercase all tags'
	 *  'recount tag use'
	 *  'purge unused tags'
	 */
	public function display_form() {
		global $page, $database;

		$html = "";
		$html .= $this->button("All tags to lowercase", "lowercase_all_tags", true);
		$html .= $this->button("Recount tag use", "recount_tag_user", false);
		$html .= $this->button("Purge unused tags", "purge_unused_tags", true);
		$html .= $this->button("Download all images", "image_dump", false);
		if($database->engine->name == "mysql") {
			$html .= $this->button("Download database contents", "database_dump", false);
			$html .= $this->button("Reset image IDs", "reset_image_ids", true);
		}
		$page->add_block(new Block("Misc Admin Tools", $html));
	}

	public function display_dbq($terms) {
		global $page;

		$h_terms = html_escape($terms);

		$html = make_form(make_link("admin/delete_by_query"), "POST") . "
				<input type='button' class='shm-unlocker' data-unlock-sel='#dbqsubmit' value='Unlock'>
				<input type='hidden' name='query' value='$h_terms'>
				<input type='submit' id='dbqsubmit' disabled='true' value='Delete All These Images'>
			</form>
		";
		$page->add_block(new Block("List Controls", $html, "left"));
	}
}
?>
