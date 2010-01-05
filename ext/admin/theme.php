<?php

class AdminPageTheme extends Themelet {
	/*
	 * Show the basics of a page, for other extensions to add to
	 */
	public function display_page(Page $page) {
		$page->set_title("Admin Tools");
		$page->set_heading("Admin Tools");
		$page->add_block(new NavBlock());
	}

	/*
	 * Show a form which links to admin_utils with POST[action] set to one of:
	 *  'lowercase all tags'
	 *  'recount tag use'
	 *  'purge unused tags'
	 */
	public function display_form(Page $page) {
		$html = "
			<p><form action='".make_link("admin_utils")."' method='POST'>
				<select name='action'>
					<option value='lowercase all tags'>All tags to lowercase</option>
					<option value='recount tag use'>Recount tag use</option>
					<option value='purge unused tags'>Purge unused tags</option>
					<option value='database dump'>Download database contents</option>
					<option value='convert to innodb'>Convert database to InnoDB (MySQL only)</option>
				</select>
				<input type='submit' value='Go'>
			</form>
		";
		$page->add_block(new Block("Misc Admin Tools", $html));
	}
}
?>
