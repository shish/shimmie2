<?php

class BulkAdd extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "bulk_add")) {
			global $user;
			if($user->is_admin() && isset($_POST['dir'])) {
				set_time_limit(0);

				global $page;
				$page->set_title("Adding folder");
				$page->set_heading("Adding folder");
				$page->add_side_block(new NavBlock());
				$this->add_dir($_POST['dir']);
			}
		}

		if(is_a($event, 'AdminBuildingEvent')) {
			global $page;
			$page->add_main_block(new Block("Bulk Add", $this->build_bulkadd()));
		}
	}
// }}}
// do the adding {{{
	private function add_image($tmpname, $filename, $tags) {
		global $config;

		$ok = false;
		
		if(filesize($tmpname) > $config->get_int('upload_size')) {
//			$page->add_main_block(new Block("Error with ".html_escape($filename),
//				"File too large (".filesize($file['tmp_name'])." &gt; ".
//				($config->get_int('upload_size')).")"));
		}
		else if(!($info = getimagesize($tmpname))) {
//			$page->add_main_block(new Block("Error with ".html_escape($file['name']),
//				"PHP doesn't recognise this as an image file"));
		}
		else {
			$image = new Image($tmpname, $filename, $tags);
		
			if($image->is_ok()) {
				send_event(new UploadingImageEvent($image));
				$ok = true;
			}
		}

		return $ok;
	}

	private function add_dir($base, $subdir="") {
		global $page;
		
		if(!is_dir($base)) {
			$page->add_main_block(new Block("Error", "$base is not a directory"));
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

		$page->add_main_block(new Block("Adding $subdir", $list));
	}
// }}}
// admin page HTML {{{
	private function build_bulkadd() {
		$html = "
			Add a folder full of images; any subfolders will have their names
			used as tags for the images within.
			<br>Note: this is the folder as seen by the server -- you need to
			upload via FTP or something first.
			
			<p><form action='".make_link("bulk_add")."' method='POST'>
				Directory to add: <input type='text' name='dir' size='40'>
				<input type='submit' value='Add'>
			</form>
		";
		return $html;
	}
// }}}
}
add_event_listener(new BulkAdd());
?>
