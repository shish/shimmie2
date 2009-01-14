<?php

class UploadTheme extends Themelet {
	public function display_block(Page $page) {
		$page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
	}

	public function display_full(Page $page) {
		$page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "left", 20));
	}

	public function display_page(Page $page) {
		global $config;
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");

		$upload_list = "";
		for($i=0; $i<$config->get_int('upload_count'); $i++) {
			$n = $i + 1;
			$width = $tl_enabled ? "35%" : "80%";
			$upload_list .= "
				<tr>
					<td width='50'>File $n</td>
					<td width='250'><input accept='image/jpeg,image/png,image/gif' id='data$i' name='data$i' type='file'></td>
			";
			if($tl_enabled) {
				$upload_list .= "
					<td width='50'>URL $n</td>
					<td width='250'><input id='url$i' name='url$i' type='text'></td>
				";
			}
			$upload_list .= "
				</tr>
			";
		}
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		$html = "
			<form enctype='multipart/form-data' action='".make_link("upload")."' method='POST'>
				<table id='large_upload_form'>
					$upload_list
					<tr><td>Tags</td><td colspan='3'><input id='tagBox' name='tags' type='text' value='tagme' autocomplete='off'></td></tr>
					<tr><td>Source</td><td colspan='3'><input name='source' type='text'></td></tr>
					<tr><td colspan='4'><input type='submit' value='Post'></td></tr>
				</table>
			</form>
			<div id='upload_completions' style='clear: both;'><small>(Max file size is $max_kb)</small></div>
		";

		if($tl_enabled) {
			global $config;
			$link = make_link("upload");
			$title = "Upload to " . $config->get_string('title');
			$html .= '<p><a href="javascript:location.href=&quot;' .
				$link . '?url=&quot;+location.href+&quot;&amp;tags=&quot;+prompt(&quot;enter tags&quot;)">' .
				$title . '</a> (Drag & drop onto your bookmarks toolbar, then click when looking at an image)';
		}

		$page->set_title("Upload");
		$page->set_heading("Upload");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Upload", $html, "main", 20));
	}

	public function display_upload_status(Page $page, $ok) {
		if($ok) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link());
		}
		else {
			$page->set_title("Upload Status");
			$page->set_heading("Upload Status");
			$page->add_block(new NavBlock());
		}
	}

	public function display_upload_error(Page $page, $title, $message) {
		$page->add_block(new Block($title, $message));
	}

	protected function build_upload_block() {
		global $config;

		$upload_list = "";
		for($i=0; $i<$config->get_int('upload_count'); $i++) {
			if($i == 0) $style = ""; // "style='display:visible'";
			else $style = "style='display:none'";
			$upload_list .= "<input accept='image/jpeg,image/png,image/gif' size='10' ".
				"id='data$i' name='data$i' $style onchange=\"showUp('data".($i+1)."')\" type='file'>\n";
		}
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		// <input type='hidden' name='max_file_size' value='$max_size' />
		return "
			<form enctype='multipart/form-data' action='".make_link("upload")."' method='POST'>
				$upload_list
				<input id='tagBox' name='tags' type='text' value='tagme' autocomplete='off'>
				<input type='submit' value='Post'>
			</form>
			<div id='upload_completions' style='clear: both;'><small>(Max file size is $max_kb)</small></div>
		";
	}
}
?>
