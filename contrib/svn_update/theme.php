<?php

class SVNUpdateTheme extends Themelet {
	public function display_form($page) {
		$html = "
			<a href='".make_link("update/log")."'>Check for Updates</a>
		";
		$page->add_block(new Block("Update", $html));
	}

	public function display_update_todo($page, $log) {
		$h_log = html_escape($log);
		$html = "
			<textarea rows='20' cols='80'>$h_log</textarea>
			<br/><a href='".make_link("update/run")."'>Install Updates</a>
		";

		$page->set_title("Updates Available");
		$page->set_heading("Updates Available");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Updates", $html));
	}

	public function display_update_log($page, $log) {
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
