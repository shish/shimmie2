<?php
/*
 * Name: Uploader
 * Author: Shish
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
	public function DataUploadEvent(User $user, $tmpname, $metadata) {
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
class Upload implements Extension {
	var $theme;
// event handling {{{
	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		// fucking PHP "security" measures -_-;;;
		$free_num = @disk_free_space(realpath("./images/"));
		if($free_num === FALSE) {
			$is_full = false;
		}
		else {
			$is_full = $free_num < 100*1024*1024;
		}

		if($event instanceof InitExtEvent) {
			$config->set_default_int('upload_count', 3);
			$config->set_default_int('upload_size', '1MB');
			$config->set_default_bool('upload_anon', false);
			$config->set_default_bool('upload_replace', true);
		}

		if($event instanceof PostListBuildingEvent) {
			if($this->can_upload($user)) {
				if($is_full) {
					$this->theme->display_full($page);
				}
				else {
					$this->theme->display_block($page);
				}
			}
		}

		if($event instanceof PageRequestEvent) {
		
			if ($event->page_matches("upload/replace"))
			{
				/* Upload & Replace Image Request */
				
				if (!$config->get_bool("upload_replace")) {
					throw new UploadException("Upload Replacing Images is not enabled.");
				}
				
				// check if the user is an administrator and can upload files.
				if (!$user->is_admin()) {
					$this->theme->display_permission_denied($page);
				}
				else
				{
					if($is_full) {
						throw new UploadException("Can not replace Image: disk nearly full");
					}
					// Try to get the image ID
					$image_id = int_escape($event->get_arg(0));
					if (empty($image_id)) {
						$image_id = isset($_POST['image_id']) ? $_POST['image_id'] : null;
					}
					if (empty($image_id)) {
						throw new UploadException("Can not replace Image: No valid Image ID given.");
					}
						
					$image_old = Image::by_id($image_id);
					if(is_null($image_old)) {
						$this->theme->display_error($page, "Image not found", "No image in the database has the ID #$image_id");
					}
						
					if(count($_FILES) + count($_POST) > 0)
					{
						if (count($_FILES) > 1) {
							throw new UploadException("Can not upload more than one image for replacing.");
						}
						
						$source = isset($_POST['source']) ? $_POST['source'] : null;
						$tags = ''; // Tags aren't changed when uploading. Set to null to stop PHP warnings.
						
						if (count($_FILES)) {
							foreach($_FILES as $file) {
								$ok = $this->try_upload($file, $tags, $source, $image_id);
								break; // leave the foreach loop.
							}
						} else {
							foreach($_POST as $name => $value) {
								if(substr($name, 0, 3) == "url" && strlen($value) > 0) {
									$ok = $this->try_transload($value, $tags, $source, $image_id);
									break; // leave the foreach loop.
								}
							}
						}
						$this->theme->display_upload_status($page, $ok);
					}
					else if(!empty($_GET['url']))
					{
						$url = $_GET['url'];
						$ok = $this->try_transload($url, $tags, $url, $image_id);
						$this->theme->display_upload_status($page, $ok);		
					}
					else
					{
						$this->theme->display_replace_page($page, $image_id);
					}
				} // END of if admin / can_upload
			}
			else if ($event->page_matches("upload"))
			{
				if(!$this->can_upload($user)) {
					$this->theme->display_permission_denied($page);
				} else {
					/* Regular Upload Image */
					if(count($_FILES) + count($_POST) > 0)
					{
						$tags = Tag::explode($_POST['tags']);
						$source = isset($_POST['source']) ? $_POST['source'] : null;
						$ok = true;
						foreach($_FILES as $file) {
							$ok = $ok & $this->try_upload($file, $tags, $source);
						}
						foreach($_POST as $name => $value) {
							if(substr($name, 0, 3) == "url" && strlen($value) > 0) {
								$ok = $ok & $this->try_transload($value, $tags, $source);
							}
						}

						$this->theme->display_upload_status($page, $ok);
					}
					else if(!empty($_GET['url']))
					{
						$url = $_GET['url'];
						$tags = array('tagme');
						if(!empty($_GET['tags']) && $_GET['tags'] != "null") {
							$tags = Tag::explode($_GET['tags']);
						}
						$ok = $this->try_transload($url, $tags, $url);
						$this->theme->display_upload_status($page, $ok);
					}
					else
					{
						if ($is_full) {
							$this->theme->display_full($page);
						} else {
							$this->theme->display_page($page);
						}
					}
				} // END of if  can_upload
			}
		} // END of if PageRequestEvent

		if($event instanceof SetupBuildingEvent) {
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
			$sb->add_label("<i>PHP's Upload Limit = ".ini_get('max_file_uploads')."</i><br/>");
			$sb->add_int_option("upload_count", "Max uploads: ");
			$sb->add_label("<br/><i>PHP's Max Size Upload = ".ini_get('upload_max_filesize')."</i><br/>");
			$sb->add_shorthand_int_option("upload_size", "<br/>Max size per file: ");
			$sb->add_bool_option("upload_anon", "<br/>Allow anonymous uploads: ");
			$sb->add_bool_option("upload_replace", "<br/>Allow replacing images: ");
			$sb->add_choice_option("transload_engine", $tes, "<br/>Transload: ");
			$event->panel->add_block($sb);
		}

		if($event instanceof DataUploadEvent) {
			if($is_full) {
				throw new UploadException("Upload failed; disk nearly full");
			}
			if(filesize($event->tmpname) > $config->get_int('upload_size')) {
				$size = to_shorthand_int(filesize($event->tmpname));
				$limit = to_shorthand_int($config->get_int('upload_size'));
				throw new UploadException("File too large ($size &gt; $limit)");
			}
		}
	}
// }}}
// do things {{{
	private function can_upload($user) {
		global $config;
		return ($config->get_bool("upload_anon") || !$user->is_anonymous());
	}

	// Helper function based on the one from the online PHP Documentation
	// which is licensed under Creative Commons Attribution 3.0 License
	// TODO: Make these messages user/admin editable
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
	
	private function try_upload($file, $tags, $source, $replace='') {
		global $page;
		global $config;
		global $user;

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

	private function try_transload($url, $tags, $source, $replace='') {
		global $page;
		global $config;

		$ok = true;

		if(empty($source)) $source = $url;

		// PHP falls back to system default if /tmp fails, can't we just
		// use the system default to start with? :-/
		$tmp_filename = tempnam("/tmp", "shimmie_transload");
		$filename = basename($url);

		if($config->get_string("transload_engine") == "fopen") {
			$fp = @fopen($url, "r");
			if(!$fp) {
				$this->theme->display_upload_error($page, "Error with ".html_escape($filename),
					"Error reading from ".html_escape($url));
				return false;
			}
			$data = "";
			$length = 0;
			while(!feof($fp) && $length <= $config->get_int('upload_size')) {
				$data .= fread($fp, 8192);
				$length = strlen($data);
			}
			fclose($fp);

			$fp = fopen($tmp_filename, "w");
			fwrite($fp, $data);
			fclose($fp);
		}

		if($config->get_string("transload_engine") == "curl" && function_exists("curl_init")) {
			$ch = curl_init($url);
			$fp = fopen($tmp_filename, "w");

			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".VERSION);

			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		}

		if($config->get_string("transload_engine") == "wget") {
			$ua = "Shimmie-".VERSION;
			$s_url = escapeshellarg($url);
			$s_tmp = escapeshellarg($tmp_filename);
			system("wget $s_url --output-document=$s_tmp --user-agent=$ua --referer=$s_url");
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
add_event_listener(new Upload(), 40); // early, so it can stop the DataUploadEvent before any data handlers see it
?>
