<?php

class BulkAdd extends Extension {
	var $theme;
// event handler {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("bulk_add", "BulkAddTheme");

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "bulk_add")) {
			global $user;
			if($user->is_admin() && isset($_POST['dir'])) {
				set_time_limit(0);

				$this->add_dir($_POST['dir']);
				$this->theme->display_upload_results($event->page);
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$this->theme->display_admin_block($page);
		}
	}
// }}}
// do the adding {{{
	private function add_image($tmpname, $filename, $tags) {
		global $config;

		$ok = false;
		
		if(filesize($tmpname) > $config->get_int('upload_size')) {
//			$page->add_block(new Block("Error with ".html_escape($filename),
//				"File too large (".filesize($file['tmp_name'])." &gt; ".
//				($config->get_int('upload_size')).")"));
		}
		else if(!($info = getimagesize($tmpname))) {
//			$page->add_block(new Block("Error with ".html_escape($file['name']),
//				"PHP doesn't recognise this as an image file"));
		}
		else {
			$image = new Image($tmpname, $filename, $tags);
		
			if($image->is_ok()) {
				global $user;
				$uie = new UploadingImageEvent($user, $image);
				send_event($uie);
				$ok = !$uie->vetoed;
			}
		}

		return $ok;
	}

	private function add_dir($base, $subdir="") {
		global $page;
		
		if(!is_dir($base)) {
			$this->theme->add_status("Error", "$base is not a directory");
			return;
		}

		$list = "";
		
		$dir = opendir("$base/$subdir");
		while($filename = readdir($dir)) {
			$fullpath = "$base/$subdir/$filename";
		
			if(is_dir($fullpath)) {
				if($filename[0] != ".") {
					$this->add_dir($base, "$subdir/$filename");
				}
			}
			else {
				$tmpfile = $fullpath;
				$list .= "<br>".html_escape("$subdir/$filename (".str_replace("/", ",", $subdir).")...");
				if($this->add_image($tmpfile, $filename, str_replace("/", " ", $subdir))) {
					$list .= "ok\n";
				}
				else {
					$list .= "failed\n";
				}
			}
		}
		closedir($dir);

		$this->theme->add_status("Adding $subdir", $list);
	}
// }}}
}
add_event_listener(new BulkAdd());
?>
