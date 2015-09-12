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

	/**
	 * @param string $name
	 * @param string $action
	 * @param bool $protected
	 * @return string
	 */
	protected function button(/*string*/ $name, /*string*/ $action, /*boolean*/ $protected=false) {
		$c_protected = $protected ? " protected" : "";
		$html = make_form(make_link("admin/$action"), "POST", false, "admin$c_protected");
		if($protected) {
			$html .= "<input type='submit' id='$action' value='$name' disabled='disabled'>";
			$html .= "<input type='checkbox' onclick='$(\"#$action\").attr(\"disabled\", !$(this).is(\":checked\"))'>";
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
	 *  etc
	 */
	public function display_form() {
		global $page, $database;

		$html = "";
		$html .= $this->button("All tags to lowercase", "lowercase_all_tags", true);
		$html .= $this->button("Recount tag use", "recount_tag_use", false);
		if(class_exists('ZipArchive'))
			$html .= $this->button("Download all images", "download_all_images", false);
        $html .= $this->button("Download database contents", "database_dump", false);
		if($database->get_driver_name() == "mysql")
			$html .= $this->button("Reset image IDs", "reset_image_ids", true);
		$page->add_block(new Block("Misc Admin Tools", $html));

		$html = make_form(make_link("admin/set_tag_case"), "POST");
		$html .= "<input type='text' name='tag' placeholder='Enter tag with correct case' class='autocomplete_tags' autocomplete='off'>";
		$html .= "<input type='submit' value='Set Tag Case'>";
		$html .= "</form>\n";
		$page->add_block(new Block("Set Tag Case", $html));
	}

	public function dbq_html($terms) {
		$h_terms = html_escape($terms);
		$h_reason = "";
		if(class_exists("ImageBan")) {
			$h_reason = "<input type='text' name='reason' placeholder='Ban reason (leave blank to not ban)'>";
		}
		$html = make_form(make_link("admin/delete_by_query"), "POST") . "
				<input type='button' class='shm-unlocker' data-unlock-sel='#dbqsubmit' value='Unlock'>
				<input type='hidden' name='query' value='$h_terms'>
				$h_reason
				<input type='submit' id='dbqsubmit' disabled='true' value='Delete All These Images'>
			</form>
		";
		return $html;
	}
}

