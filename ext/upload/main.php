<?php
/**
 * Name: Uploader
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Allows people to upload files to the website
 */

/**
 * Occurs when some data is being uploaded.
 */
class DataUploadEvent extends Event {
	var $user, $tmpname, $metadata, $hash, $type, $image_id = -1;

	/**
	 * Some data is being uploaded.
	 * This should be caught by a file handler.
	 * @param $user The user uploading the data.
	 * @param $tmpname The temporary file used for upload.
	 * @param $metadata Info about the file, should contain at least "filename", "extension", "tags" and "source".
	 */
	public function DataUploadEvent(User $user, /*string*/ $tmpname, /*array*/ $metadata) {
		assert(file_exists($tmpname));

		$this->user = $user;
		$this->tmpname = $tmpname;

		$this->metadata = $metadata;
		$this->metadata['hash'] = md5_file($tmpname);
		$this->metadata['size'] = filesize($tmpname);

		// useful for most file handlers, so pull directly into fields
		$this->hash = $this->metadata['hash'];
		$this->type = strtolower($metadata['extension']);
	}
}

class UploadException extends SCoreException {}

/**
 * Main upload class.
 * All files that are uploaded to the site are handled through this class.
 * This also includes transloaded files as well.
 */
class Upload extends Extension {
	// early, so it can stop the DataUploadEvent before any data handlers see it
	public function get_priority() {return 40;}

	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_int('upload_count', 3);
		$config->set_default_int('upload_size', '1MB');
		$config->set_default_bool('upload_anon', false);

		// SHIT: fucking PHP "security" measures -_-;;;
		$free_num = @disk_free_space(realpath("./images/"));
		if($free_num === FALSE) {
			$this->is_full = false;
		}
		else {
			// TODO: This size limit should be configureable by the admin...
			// currently set to 100 MB
			$this->is_full = $free_num < 100*1024*1024;
		}

	}

	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $user, $page;
		if($this->can_upload($user)) {
			if($this->is_full) {
				$this->theme->display_full($page);
			}
			else {
				$this->theme->display_block($page);
			}
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$tes = array();
		$tes["Disabled"] = "none";
		if(function_exists("curl_init")) {
			$tes["cURL"] = "curl";
		}
		$tes["fopen"] = "fopen";
		$tes["WGet"] = "wget";

		$sb = new SetupBlock("Upload");
		$sb->position = 10;
		// Output the limits from PHP so the user has an idea of what they can set.
		$sb->add_int_option("upload_count", "Max uploads: ");
		$sb->add_label("<i>PHP Limit = ".ini_get('max_file_uploads')."</i>");
		$sb->add_shorthand_int_option("upload_size", "<br/>Max size per file: ");
		$sb->add_label("<i>PHP Limit = ".ini_get('upload_max_filesize')."</i>");
		$sb->add_bool_option("upload_anon", "<br/>Allow anonymous uploads: ");
		$sb->add_choice_option("transload_engine", $tes, "<br/>Transload: ");
		$event->panel->add_block($sb);
	}

	public function onDataUpload(DataUploadEvent $event) {
		global $config;
		if($this->is_full) {
			throw new UploadException("Upload failed; disk nearly full");
		}
		if(filesize($event->tmpname) > $config->get_int('upload_size')) {
			$size = to_shorthand_int(filesize($event->tmpname));
			$limit = to_shorthand_int($config->get_int('upload_size'));
			throw new UploadException("File too large ($size &gt; $limit)");
		}
	}

	public function onPageRequest($event) {
		global $config, $page, $user;

		if($event->page_matches("upload/replace")) {
			// check if the user is an administrator and can upload files.
			if(!$user->can("replace_image")) {
				$this->theme->display_permission_denied();
			}
			else {
				if($this->is_full) {
					throw new UploadException("Can not replace Image: disk nearly full");
				}
				// Try to get the image ID
				$image_id = int_escape($event->get_arg(0));
				if(empty($image_id)) {
					$image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
				}
				if(empty($image_id)) {
					throw new UploadException("Can not replace Image: No valid Image ID given.");
				}
					
				$image_old = Image::by_id($image_id);
				if(is_null($image_old)) {
					$this->theme->display_error(404, "Image not found", "No image in the database has the ID #$image_id");
				}
					
				if(count($_FILES) + count($_POST) > 0) {
					if(count($_FILES) > 1) {
						throw new UploadException("Can not upload more than one image for replacing.");
					}
					
					$source = isset($_POST['source']) ? $_POST['source'] : null;
					$tags = ''; // Tags aren't changed when uploading. Set to null to stop PHP warnings.
					
					if(count($_FILES)) {
						reset($_FILES);	// rewind to first element in array.
						foreach($_FILES as $file) {
							$ok = $this->try_upload($file, $tags, $source, $image_id);
							break; // leave the foreach loop.
						}
					}
					else {
						reset($_POST);	// rewind to first element in array.
						foreach($_POST as $name => $value) {
							if(substr($name, 0, 3) == "url" && strlen($value) > 0) {
								$ok = $this->try_transload($value, $tags, $source, $image_id);
								break; // leave the foreach loop.
							}
						}
					}
					$this->theme->display_upload_status($page, $ok);
				}
				else if(!empty($_GET['url'])) {
					$url = $_GET['url'];
					$ok = $this->try_transload($url, $tags, $url, $image_id);
					$this->theme->display_upload_status($page, $ok);		
				}
				else {
					$this->theme->display_replace_page($page, $image_id);
				}
			}
		}
		else if($event->page_matches("upload")) {
			if(!$this->can_upload($user)) {
				$this->theme->display_permission_denied();
			}
			else {
				/* Regular Upload Image */
				if(count($_FILES) + count($_POST) > 0) {
					$source = isset($_POST['source']) ? $_POST['source'] : null;
					$ok = true;
					foreach($_FILES as $name => $file) {
						$tags = $this->tags_for_upload_slot(int_escape(substr($name, 4)));
						$ok = $ok & $this->try_upload($file, $tags, $source);
					}
					foreach($_POST as $name => $value) {
						if(substr($name, 0, 3) == "url" && strlen($value) > 0) {
							$tags = $this->tags_for_upload_slot(int_escape(substr($name, 3)));
							$ok = $ok & $this->try_transload($value, $tags, $source);
						}
					}

					$this->theme->display_upload_status($page, $ok);
				}
				else if(!empty($_GET['url'])) {
					$url = $_GET['url'];
					$tags = array('tagme');
					if(!empty($_GET['tags']) && $_GET['tags'] != "null") {
						$tags = Tag::explode($_GET['tags']);
					}
							
					$ok = $this->try_transload($url, $tags, $url);
					$this->theme->display_upload_status($page, $ok);
				}
				else {
					if ($this->is_full) {
						$this->theme->display_full($page);
					} else {
						$this->theme->display_page($page);
					}
				}
			}
		}
	}

	private function tags_for_upload_slot($id) {
		if(isset($_POST["tags$id"])) {
			$tags = array_merge(Tag::explode($_POST['tags']), Tag::explode($_POST["tags$id"]));
		}
		else {
			$tags = Tag::explode($_POST['tags']);
		}
		return $tags;
	}

// do things {{{

	/**
	 * Check if a given user can upload.
	 * @param $user The user to check.
	 * @retval bool
	 */
	private function can_upload(User $user) {
		global $config;
		return ($config->get_bool("upload_anon") || !$user->is_anonymous());
	}

	/**
	 * Returns a descriptive error message for the specified PHP error code.
	 *
	 * This is a helper function based on the one from the online PHP Documentation
	 * which is licensed under Creative Commons Attribution 3.0 License
	 *
	 * TODO: Make these messages user/admin editable
	 *
	 * @param $error_code PHP error code (int)
	 * @retval String
	 */
	private function upload_error_message($error_code) {
		switch ($error_code) {
			case UPLOAD_ERR_INI_SIZE:
				return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
			case UPLOAD_ERR_FORM_SIZE:
				return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
			case UPLOAD_ERR_PARTIAL:
				return 'The uploaded file was only partially uploaded';
			case UPLOAD_ERR_NO_FILE:
				return 'No file was uploaded';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'Missing a temporary folder';
			case UPLOAD_ERR_CANT_WRITE:
				return 'Failed to write file to disk';
			case UPLOAD_ERR_EXTENSION:
				return 'File upload stopped by extension';
			default:
				return 'Unknown upload error';
		}
	}
	
	/**
	 * Handle an upload.
	 * @retval bool TRUE on upload successful.
	 */
	private function try_upload($file, $tags, $source, $replace='') {
		global $page, $config, $user;

		if(empty($source)) $source = null;

		$ok = true;

		// blank file boxes cause empty uploads, no need for error message
		if (!empty($file['name'])) {
			try {
				// check if the upload was successful
				if ($file['error'] !== UPLOAD_ERR_OK) {
					throw new UploadException($this->upload_error_message($file['error']));
				}
				
				$pathinfo = pathinfo($file['name']);
				$metadata['filename'] = $pathinfo['basename'];
				$metadata['extension'] = $pathinfo['extension'];
				$metadata['tags'] = $tags;
				$metadata['source'] = $source;
				
				/* check if we have been given an image ID to replace */
				if (!empty($replace)) {
					$metadata['replace'] = $replace;
				}
				
				$event = new DataUploadEvent($user, $file['tmp_name'], $metadata);
				send_event($event);
				if($event->image_id == -1) {
					throw new UploadException("File type not recognised");
				}
				//header("X-Shimmie-Image-ID: ".int_escape($event->image_id));
				$page->add_http_header("X-Shimmie-Image-ID: ".int_escape($event->image_id));
			}
			catch(UploadException $ex) {
				$this->theme->display_upload_error($page, "Error with ".html_escape($file['name']),
					$ex->getMessage());
				$ok = false;
			}
		}

		return $ok;
	}

	/**
	 * Handle an transload.
	 * @retval bool TRUE on transload successful.
	 */
	private function try_transload($url, $tags, $source, $replace='') {
		global $page, $config, $user;

		$ok = true;

		//Allows external source to be set.
		if(!empty($_GET['source'])){
			$source = $_GET['source'];
		}else{
			$source = $url;
		}
		
		// Checks if user is admin > check if you want locked.
		if($user->can("edit_image_lock") && !empty($_GET['locked'])){
			$locked = bool_escape($_GET['locked']);
		}
		
		// Checks if url contains rating, also checks if the rating extension is enabled.
		if($config->get_string("transload_engine", "none") != "none" && file_exists("ext/rating") && !empty($_GET['rating'])) {
			// Rating event will validate that this is s/q/e/u
			$rating = strtolower($_GET['rating']);
			$rating = $rating[0];
		}else{
			$rating = "";
		}

		// PHP falls back to system default if /tmp fails, can't we just
		// use the system default to start with? :-/
		$tmp_filename = tempnam("/tmp", "shimmie_transload");
		$filename = basename($url);

		if(!transload($url, $tmp_filename)) {
			$this->theme->display_upload_error($page, "Error with ".html_escape($filename),
				"Error reading from ".html_escape($url));
			return false;
		}

		if(filesize($tmp_filename) == 0) {
			$this->theme->display_upload_error($page, "Error with ".html_escape($filename),
				"No data found -- perhaps the site has hotlink protection?");
			$ok = false;
		}
		else {
			global $user;
			$pathinfo = pathinfo($url);
			$metadata['filename'] = $pathinfo['basename'];
			$metadata['extension'] = $pathinfo['extension'];
			$metadata['tags'] = $tags;
			$metadata['source'] = $source;
			
			/* check for locked > adds to metadata if it has */
			if(!empty($locked)){
				$metadata['locked'] = $locked ? "on" : "";
			}
						
			/* check for rating > adds to metadata if it has */
			if(!empty($rating)){
				$metadata['rating'] = $rating;
			}
			
			/* check if we have been given an image ID to replace */
			if (!empty($replace)) {
				$metadata['replace'] = $replace;
			}
			
			$event = new DataUploadEvent($user, $tmp_filename, $metadata);
			try {
				send_event($event);
			}
			catch(UploadException $ex) {
				$this->theme->display_upload_error($page, "Error with ".html_escape($url),
					$ex->getMessage());
				$ok = false;
			}
		}

		unlink($tmp_filename);

		return $ok;
	}
// }}}
}
?>
