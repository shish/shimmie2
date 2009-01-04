<?php

class SVNUpdateTheme extends Themelet {
	public function display_form(Page $page) {
		$html = "
			<a href='".make_link("update/view_changes")."'>Check for Updates</a>
		";
		$page->add_block(new Block("Update", $html));
	}

	public function display_update_todo(Page $page, $log, $branches) {
		$h_log = html_escape($log);
		$updates = "
			<textarea rows='20' cols='80'>$h_log</textarea>
			<br/>
			<form action='".make_link("update/update")."' method='POST'>
				<input type='submit' value='Install Updates'>
			</form>
		";
		$options = "";
		foreach($branches as $name => $nice) {
			$options .= "<option value='$name'>$nice</option>";
		}
		$branches = "
			<form action='".make_link("update/switch")."' method='POST'>
				<select name='branch'>
					$options
				</select>
				<input type='submit' value='Change Branch'>
			</form>
		";

		$page->set_title("Updates Available");
		$page->set_heading("Updates Available");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Updates For Current Branch", $updates));
		$page->add_block(new Block("Available Branches", $branches));
	}

	public function display_update_log(Page $page, $log) {
		$h_log = html_escape($log);
		$html = "
			<textarea rows='20' cols='80'>$h_log</textarea>
		";

		$page->set_title("Update Log");
		$page->set_heading("Update Log");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Update Log", $html));
	}
}
?>
