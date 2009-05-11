<?php
/**
 * Name: Bulk Add
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Bulk add server-side images
 * Documentation:
 *  Upload the images into a new directory via ftp or similar, go to
 *  shimmie's admin page and put that directory in the bulk add box.
 *  If there are subdirectories, they get used as tags (eg if you
 *  upload into <code>/home/bob/uploads/holiday/2008/</code> and point
 *  shimmie at <code>/home/bob/uploads</code>, then images will be
 *  tagged "holiday 2008")
 */

class BulkAdd extends SimpleExtension {
	public function onPageRequest($event) {
		global $page, $user;
		if($event->page_matches("bulk_add")) {
			if($user->is_admin() && isset($_POST['dir'])) {
				set_time_limit(0);

				$this->add_dir($_POST['dir']);
				$this->theme->display_upload_results($page);
			}
		}
	}

	public function onAdminBuilding($event) {
		global $page;
		$this->theme->display_admin_block($page);
	}


	private function add_image($tmpname, $filename, $tags) {
		if(file_exists($tmpname)) {
			global $user;
			$pathinfo = pathinfo($filename);
			$metadata['filename'] = $pathinfo['basename'];
			$metadata['extension'] = $pathinfo['extension'];
			$metadata['tags'] = $tags;
			$metadata['source'] = null;
			try {
				$event = new DataUploadEvent($user, $tmpname, $metadata);
				send_event($event);
			}
			catch(Exception $ex) {
				return $ex->getMessage();
			}
		}
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

			if(is_link($fullpath)) {
				// ignore
			}
			else if(is_dir($fullpath)) {
				if($filename[0] != ".") {
					$this->add_dir($base, "$subdir/$filename");
				}
			}
			else {
				$tmpfile = $fullpath;
				$tags = $subdir;
				$tags = str_replace("/", " ", $tags);
				$tags = str_replace("__", " ", $tags);
				$tags = trim($tags);
				$list .= "<br>".html_escape("$subdir/$filename (".str_replace(" ", ", ", $tags).")... ");
				$error = $this->add_image($tmpfile, $filename, $tags);
				if(is_null($error)) {
					$list .= "ok\n";
				}
				else {
					$list .= "failed:<br>$error\n";
				}
			}
		}
		closedir($dir);

		if(strlen($list) > 0) {
			$this->theme->add_status("Adding $subdir", $list);
		}
	}
}
?>
