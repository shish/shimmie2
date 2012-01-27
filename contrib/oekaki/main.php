<?php
/*
 * Name: [Beta] Oekaki
 * Author: Shish
 * Description: ChibiPaint-based Oekaki uploader
 */

class Oekaki extends SimpleExtension {
	public function onPageRequest($event) {
		global $user, $page;

		if($event->page_matches("oekaki")) {
			if(!$this->can_upload($user)) {
				$this->theme->display_permission_denied($page);
			}

			if($event->get_arg(0) == "create") {
				$this->theme->display_page();
			}
			if($event->get_arg(0) == "upload") {
				// FIXME: this allows anyone to upload anything to /data ...
				// hardcoding the ext to .png should stop the obvious exploit,
				// but more checking may be wise
				if(isset($_FILES["picture"])) {
					header('Content-type: text/plain');

					$uploaddir = './data/oekaki_unclaimed/';
					if(!file_exists($uploaddir)) mkdir($uploaddir, 0755, true);
					$file = $_FILES['picture']['name'];
					$ext = (strpos($file, '.') === FALSE) ? '' : substr($file, strrpos($file, '.'));
					$uploadname = $_SERVER['REMOTE_ADDR'] . "." . time();
					$uploadfile = $uploaddir . $uploadname;

					log_info("oekaki", "Uploading file [$uploadname]");

					$success = TRUE;
					if (isset($_FILES["chibifile"]))
						$success = $success && move_uploaded_file($_FILES['chibifile']['tmp_name'], $uploadfile . ".chi");

					// hardcode the ext, so nobody can upload "foo.php"
					$success = $success && move_uploaded_file($_FILES['picture']['tmp_name'], $uploadfile . ".png"); # $ext);
					if ($success) {
						echo "CHIBIOK\n";
					} else {
						echo "CHIBIERROR\n";
					}
				}
				else {
					echo "CHIBIERROR No Data\n";
				}
			}
			if($event->get_arg(0) == "claim") {
				// FIXME: move .chi to data/oekaki/$ha/$hash mirroring images and thumbs
				// FIXME: .chi viewer?
				// FIXME: clean out old unclaimed images?
				$pattern = './data/oekaki_unclaimed/' . $_SERVER['REMOTE_ADDR'] . ".*.png";
				foreach(glob($pattern) as $tmpname) {
					assert(file_exists($tmpname));

					$pathinfo = pathinfo($tmpname);
					if(!array_key_exists('extension', $pathinfo)) {
						throw new UploadException("File has no extension");
					}
					log_info("oekaki", "Processing file [{$pathinfo['filename']}]");
					$metadata['filename'] = 'oekaki.png';
					$metadata['extension'] = $pathinfo['extension'];
					$metadata['tags'] = 'oekaki tagme';
					$metadata['source'] = null;
					$event = new DataUploadEvent($user, $tmpname, $metadata);
					send_event($event);
					if($event->image_id == -1) {
						throw new UploadException("File type not recognised");
					}
					else {
						unlink($tmpname);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$event->image_id));
					}
				}
			}
		}
	}

	// FIXME: "edit this image" button on existing images?
	function onPostListBuilding($event) {
		global $user, $page;
		if($this->can_upload($user)) {
			$this->theme->display_block($page);
		}
	}

	private function can_upload($user) {
		global $config;
		return ($config->get_bool("upload_anon") || !$user->is_anonymous());
	}
}
?>
