<?php

class UploadTheme extends Themelet {
	public function display_block($page) {
		$page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
	}

	public function display_upload_status($page, $ok) {
		if($ok) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link("index"));
		}
		else {
			$page->set_title("Upload Status");
			$page->set_heading("Upload Status");
			$page->add_block(new NavBlock());
		}
	}

	public function display_upload_error($page, $title, $message) {
		$page->add_block(new Block($title, $message));
	}

	private function build_upload_block() {
		global $config;

		$upload_list = "";
		for($i=0; $i<$config->get_int('upload_count'); $i++) {
			if($i == 0) $style = ""; // "style='display:visible'";
			else $style = "style='display:none'";
			$upload_list .= "<input accept='image/jpeg,image/png,image/gif' size='10' ".
				"id='data$i' name='data$i' $style onchange=\"showUp('data".($i+1)."')\" type='file'>\n";
		}
		$max_size = $config->get_int('upload_size');
		$max_kb = (int)($max_size / 1024);
		// <input type='hidden' name='max_file_size' value='$max_size' />
		return "
			<form enctype='multipart/form-data' action='".make_link("upload")."' method='POST'>
				$upload_list
				<input id='tagBox' name='tags' type='text' value='tagme' autocomplete='off'>
				<input type='submit' value='Post'>
			</form>
			<div id='upload_completions' style='clear: both;'><small>(Max file size is {$max_kb}KB)</small></div>
		";
	}
}
?>
