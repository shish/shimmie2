<?php

class Upload extends Extension {
// event handling {{{
	public function receive_event($event) {
		global $page;
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			if($this->can_upload()) {
				$page->add_side_block(new Block("Upload", $this->build_upload_block()), 20);
			}
		}
		if(is_a($event, 'PageRequestEvent') && ($event->page == "upload")) {
			if($this->can_upload()) {
				global $config;
				global $page;

				$ok = true;
				foreach($_FILES as $file) {
					$ok = $ok & $this->try_upload($file);
				}

				$this->show_result($ok);
			}
			else {
				$page->set_title("Upload Denied");
				$page->set_heading("Upload Denied");
				$page->add_side_block(new NavBlock());
				$page->add_main_block(new Block("Error", "Anonymous posting is disabled"));
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Upload");
			$sb->add_label("Max Uploads: ");
			$sb->add_int_option("upload_count");
			$sb->add_label("<br>Max size per file: ");
			$sb->add_shorthand_int_option("upload_size");
			$sb->add_label("<br>Allow anonymous upoads: ");
			$sb->add_bool_option("upload_anon");
			$event->panel->add_main_block($sb, 10);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_int_from_post("upload_count");
			$event->config->set_int_from_post("upload_size");
			$event->config->set_bool_from_post("upload_anon");
		}
	}
// }}}
// do things {{{
	private function can_upload() {
		global $config, $user;
		return $config->get_bool("upload_anon") || ($user->id != $config->get_int("anon_id"));
	}

	private function try_upload($file) {
		global $page;
		global $config;

		$ok = false;
		
		if(!file_exists($file['tmp_name'])) {
			// this happens normally with blank file boxes
		}
		else if(filesize($file['tmp_name']) > $config->get_int('upload_size')) {
			$page->add_main_block(new Block("Error with ".html_escape($file['name']),
				"File too large (".filesize($file['tmp_name'])." &gt; ".
				($config->get_int('upload_size')).")"));
		}
		else if(!($info = getimagesize($file['tmp_name']))) {
			$page->add_main_block(new Block("Error with ".html_escape($file['name']),
				"PHP doesn't recognise this as an image file"));
		}
		else {
			$image = new Image($file['tmp_name'], $file['name'], $_POST['tags']);
		
			if($image->is_ok()) {
				send_event(new UploadingImageEvent($image));
				$ok = true;
			}
			else {
				$page->add_main_block(new Block("Error with ".html_escape($file['name']),
					"Something is not right!"));
			}
		}

		return $ok;
	}

	private function show_result($ok) {
		global $page;

		if($ok) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link("index"));
		}
		else {
			$page->set_title("Upload Status");
			$page->set_heading("Upload Status");
			$page->add_side_block(new NavBlock());
			$page->add_main_block(new Block("OK?",
					"If there are no errors here, things should be OK \\o/"));
		}
	}
// }}}
// HTML {{{
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
// }}}
}
add_event_listener(new Upload());
?>
