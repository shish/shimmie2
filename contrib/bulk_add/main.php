<?php
/*
 * Name: Bulk Add
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Bulk add server-side images
 * Documentation:
 *  Upload the images into a new directory via ftp or similar, go to
 *  shimmie's admin page and put that directory in the bulk add box.
 *  If there are subdirectories, they get used as tags (eg if you
 *  upload into <code>/home/bob/uploads/holiday/2008/</code> and point
 *  shimmie at <code>/home/bob/uploads</code>, then images will be
 *  tagged "holiday 2008")
 *  <p><b>Note:</b> requires the "admin" extension to be enabled
 */

class BulkAdd extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;
		if($event->page_matches("bulk_add")) {
			if($user->is_admin() && $user->check_auth_token() && isset($_POST['dir'])) {
				set_time_limit(0);
				$this->add_dir($_POST['dir']);
				$this->theme->display_upload_results($page);
			}
		}
	}

	public function onAdminBuilding(AdminBuildingEvent $event) {
		$this->theme->display_admin_block();
	}

	/**
	 * Generate the necessary DataUploadEvent for a given image and tags.
	 */
	private function add_image($tmpname, $filename, $tags) {
		assert(file_exists($tmpname));

		global $user;
		$pathinfo = pathinfo($filename);
		if(!array_key_exists('extension', $pathinfo)) {
			throw new UploadException("File has no extension");
		}
		$metadata['filename'] = $pathinfo['basename'];
		$metadata['extension'] = $pathinfo['extension'];
		$metadata['tags'] = $tags;
		$metadata['source'] = null;
		$event = new DataUploadEvent($user, $tmpname, $metadata);
		send_event($event);
		if($event->image_id == -1) {
			throw new UploadException("File type not recognised");
		}
	}

	private function add_dir($base, $subdir="") {
		global $page;

		if(!is_dir($base)) {
			$this->theme->add_status("Error", "$base is not a directory");
			return;
		}

		$list = "";

		foreach(glob("$base/$subdir/*") as $fullpath) {
			$fullpath = str_replace("//", "/", $fullpath);
			$shortpath = str_replace($base, "", $fullpath);

			if(is_link($fullpath)) {
				// ignore
			}
			else if(is_dir($fullpath)) {
				$this->add_dir($base, str_replace($base, "", $fullpath));
			}
			else {
				$pathinfo = pathinfo($fullpath);
				$tags = $subdir;
				$tags = str_replace("/", " ", $tags);
				$tags = str_replace("__", " ", $tags);
				$tags = trim($tags);
				$list .= "<br>".html_escape("$shortpath (".str_replace(" ", ", ", $tags).")... ");
				try{
					$this->add_image($fullpath, $pathinfo["basename"], $tags);
					$list .= "ok\n";
				}
				catch(Exception $ex) {
					$list .= "failed:<br>". $ex->getMessage();
				}
			}
		}

		if(strlen($list) > 0) {
			$this->theme->add_status("Adding $subdir", $list);
		}
	}
}
?>
