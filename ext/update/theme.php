<?php
class UpdateTheme extends Themelet {
	public function display_admin_block(){
		global $page, $config;

		$html = "".
			"<b>Current Commit</b>: ".$config->get_string('commit_hash')." | (".$config->get_string('update_time').")".
			"<br><b>Latest Commit</b>: <span id='updatecheck'>Loading...</span>".
			"<br><a href='" . make_link('update/download') . "' id='updatelink'></a>";
		//TODO: Show warning before use.
		$page->add_block(new Block("Software Update", $html, "main", 75));
	}
}

