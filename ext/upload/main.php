<?php

define("UPLOAD_DEFAULT_MAX_SIZE", 256000);
define("UPLOAD_DEFAULT_COUNT", 3);

class Upload extends Extension {
	var $theme;
// event handling {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("upload", "UploadTheme");
		
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			if($this->can_upload()) {
				$this->theme->display_block($event->page_object);
			}
		}
		if(is_a($event, 'PageRequestEvent') && ($event->page == "upload")) {
			if($this->can_upload()) {
				global $page;

				$ok = true;
				foreach($_FILES as $file) {
					$ok = $ok & $this->try_upload($file);
				}

				$this->theme->display_upload_status($event->page_object, $ok);
			}
			else {
				$this->theme->display_error($event->page_object, "Upload Denied", "Anonymous posting is disabled");
			}
		}

		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Upload");
			$sb->position = 10;
			$sb->add_int_option("upload_count", "Max uploads: ");
			$sb->add_shorthand_int_option("upload_size", "<br>Max size per file: ");
			$sb->add_bool_option("upload_anon", "<br>Allow anonymous uploads: ");
			$event->panel->add_block($sb);
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
		return $config->get_bool("upload_anon", false) || !$user->is_anonymous();
	}

	private function try_upload($file) {
		global $page;
		global $config;

		$ok = false;
		
		if(!file_exists($file['tmp_name'])) {
			// this happens normally with blank file boxes
			$ok = true;
		}
		else if(filesize($file['tmp_name']) > $config->get_int('upload_size', UPLOAD_DEFAULT_MAX_SIZE)) {
			$this->theme->display_upload_error($page, "Error with ".html_escape($file['name']),
				"File too large (".filesize($file['tmp_name'])." &gt; ".
				($config->get_int('upload_size', UPLOAD_DEFAULT_MAX_SIZE)).")");
		}
		else if(!($info = getimagesize($file['tmp_name']))) {
			$this->theme->display_upload_error("Error with ".html_escape($file['name']),
				"PHP doesn't recognise this as an image file");
		}
		else {
			$image = new Image($file['tmp_name'], $file['name'], $_POST['tags']);
		
			if($image->is_ok()) {
				$event = new UploadingImageEvent($image);
				send_event($event);
				$ok = $event->ok;
			}
			else {
				$this->theme->display_upload_error("Error with ".html_escape($file['name']),
					"Something is not right!");
			}
		}

		return $ok;
	}
// }}}
}
add_event_listener(new Upload());
?>
