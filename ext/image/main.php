<?php
/*
 * A class to handle adding / getting / removing image
 * files from the disk
 */
class ImageIO extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			$config->set_default_int('thumb_width', 192);
			$config->set_default_int('thumb_height', 192);
			$config->set_default_int('thumb_quality', 75);
			$config->set_default_int('thumb_mem_limit', '8MB');
			
			$config->set_default_string('image_ilink', '$base/image/$id.$ext');
			$config->set_default_string('image_tlink', '$base/thumb/$id.jpg');
			$config->set_default_string('image_tip', '$tags // $size // $filesize');
		}

		if(is_a($event, 'PageRequestEvent')) {
			$num = $event->get_arg(0);
			$matches = array();
			if(!is_null($num) && preg_match("/(\d+)/", $num, $matches)) {
				$num = $matches[1];
				
				if($event->page_name == "image") {
					$this->send_file($num, "image");
				}
				else if($event->page_name == "thumb") {
					$this->send_file($num, "thumb");
				}
			}
		}

		if(is_a($event, 'UploadingImageEvent')) {
			$error = $this->add_image($event->image);
			if(!empty($error)) $event->veto($error);
		}

		if(is_a($event, 'ImageDeletionEvent')) {
			$this->remove_image($event->image);
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Image Options");
			$sb->position = 30;
			$sb->add_text_option("image_ilink", "Image link: ");
			$sb->add_text_option("image_tlink", "<br>Thumbnail link: ");
			$sb->add_text_option("image_tip", "<br>Image tooltip: ");
			$event->panel->add_block($sb);

			$thumbers = array();
			$thumbers['Built-in GD'] = "gd";
			$thumbers['ImageMagick'] = "convert";

			$sb = new SetupBlock("Thumbnailing");
			$sb->add_choice_option("thumb_engine", $thumbers, "Engine: ");

			$sb->add_label("<br>Size ");
			$sb->add_int_option("thumb_width");
			$sb->add_label(" x ");
			$sb->add_int_option("thumb_height");
			$sb->add_label(" px at ");
			$sb->add_int_option("thumb_quality");
			$sb->add_label(" % quality ");

			$sb->add_shorthand_int_option("thumb_mem_limit", "<br>Max memory use: ");

			$event->panel->add_block($sb);
		}
	}
// }}}
// add image {{{
	private function is_dupe($hash) {
		global $database;
		return $database->db->GetRow("SELECT * FROM images WHERE hash=?", array($hash));
	}

	private function read_file($fname) {
		$fp = fopen($fname, "r");
		if(!$fp) return false;

		$data = fread($fp, filesize($fname));
		fclose($fp);

		return $data;
	}

	private function make_thumb($inname, $outname) {
		global $config;
		
		$ok = false;
		
		switch($config->get_string("thumb_engine")) {
			default:
			case 'gd':
				$ok = $this->make_thumb_gd($inname, $outname);
				break;
			case 'convert':
				$ok = $this->make_thumb_convert($inname, $outname);
				break;
		}

		return $ok;
	}

// IM thumber {{{
	private function make_thumb_convert($inname, $outname) {
		global $config;

		$w = $config->get_int("thumb_width");
		$h = $config->get_int("thumb_height");
		$q = $config->get_int("thumb_quality");
		$mem = $config->get_int("thumb_max_memory") / 1024 / 1024; // IM takes memory in MB

		// "-limit memory $mem" broken?
		exec("convert {$inname}[0] -geometry {$w}x{$h} -quality {$q} jpg:$outname");

		return true;
	}
// }}}
// GD thumber {{{
	private function make_thumb_gd($inname, $outname) {
		global $config;
		$thumb = $this->get_thumb($inname);
		return imagejpeg($thumb, $outname, $config->get_int('thumb_quality'));
	}

	private function get_thumb($tmpname) {
		global $config;

		$info = getimagesize($tmpname);
		$width = $info[0];
		$height = $info[1];

		$memory_use = (filesize($tmpname)*2) + ($width*$height*4) + (4*1024*1024);
		$memory_limit = get_memory_limit();
		
		if($memory_use > $memory_limit) {
			$w = $config->get_int('thumb_width');
			$h = $config->get_int('thumb_height');
			$thumb = imagecreatetruecolor($w, min($h, 64));
			$white = imagecolorallocate($thumb, 255, 255, 255);
			$black = imagecolorallocate($thumb, 0,   0,   0);
			imagefill($thumb, 0, 0, $white);
			imagestring($thumb, 5, 10, 24, "Image Too Large :(", $black);
			return $thumb;
		}
		else {
			$image = imagecreatefromstring($this->read_file($tmpname));
			$tsize = get_thumbnail_size($width, $height);

			$thumb = imagecreatetruecolor($tsize[0], $tsize[1]);
			imagecopyresampled(
					$thumb, $image, 0, 0, 0, 0,
					$tsize[0], $tsize[1], $width, $height
					);
			return $thumb;
		}
	}
// }}}

	private function add_image($image) {
		global $page;
		global $user;
		global $database;
		global $config;

		/*
		 * Validate things
		 */
		if(strlen(trim($image->source)) == 0) {
			$image->source = null;
		}
		if(!empty($image->source)) {
			if(!preg_match("#^(https?|ftp)://#", $image->source)) {
				$error = "Image's source isn't a valid URL";
				return $error;
			}
		}

		/*
		 * Check for an existing image
		 */
		$existing = $database->get_image_by_hash($image->hash);
		if(!is_null($existing)) {
			$error = "Image <a href='".make_link("post/view/{$existing->id}")."'>{$existing->id}</a> ".
					"already has hash {$image->hash}:<p>".build_thumb_html($existing);
			return $error;
		}

		// actually insert the info
		$database->Execute(
				"INSERT INTO images(
					owner_id, owner_ip, filename, filesize,
					hash, ext, width, height, posted, source)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, now(), ?)",
				array($user->id, $_SERVER['REMOTE_ADDR'], $image->filename, $image->filesize,
						$image->hash, $image->ext, $image->width, $image->height, $image->source));
		$image->id = $database->db->Insert_ID();

		/*
		 * If no errors: move the file from the temporary upload
		 * area to the main file store, create a thumbnail, and
		 * insert the image info into the database
		 */
		if(!copy($image->temp_filename, $image->get_image_filename())) {
			send_event(new ImageDeletionEvent($image->id));
			$error = "The image couldn't be moved from the temporary area to the
					main data store -- is the web server allowed to write to '".
					($image->get_image_filename())."'?";
			return $error;
		}
		chmod($image->get_image_filename(), 0644);

		if(!$this->make_thumb($image->get_image_filename(), $image->get_thumb_filename())) {
			send_event(new ImageDeletionEvent($image->id));
			$error="The image thumbnail couldn't be generated -- is the web
					server allowed to write to '".($image->get_thumb_filename())."'?";
			return $error;
		}
		chmod($image->get_thumb_filename(), 0644);

		send_event(new TagSetEvent($image->id, $image->get_tag_array()));

		return null;
	}
// }}}
// fetch image {{{
	private function send_file($image_id, $type) {
		global $database;
		$image = $database->get_image($image_id);

		global $page;
		if(!is_null($image)) {
			$page->set_mode("data");
			if($type == "thumb") {
				$page->set_type("image/jpeg");
				$file = $image->get_thumb_filename();
			}
			else {
				$page->set_type($image->get_mime_type());
				$file = $image->get_image_filename();
			}
		
			if(!file_exists($file)) {
				print("Can't read $file");
				exit;
			}

			$page->set_data(file_get_contents($file));

			if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
				$if_modified_since = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
			}
			else {
				$if_modified_since = "";
			}
			$gmdate_mod = gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT';

			// FIXME: should be $page->blah
			if($if_modified_since == $gmdate_mod) {
				header("HTTP/1.0 304 Not Modified");
			}
			else {
				header("Last-Modified: $gmdate_mod");
				header("Expires: Fri, 2 Sep 2101 12:42:42 GMT"); // War was beginning
			}
		}
		else {
			$page->set_title("Not Found");
			$page->set_heading("Not Found");
			$page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
			$page->add_block(new Block("Image not in database",
					"The requested image was not found in the database"));
		}
	}
// }}}
// delete image {{{
	private function remove_image($image) {
		global $database;
		$database->remove_image($image->id);
		
		unlink($image->get_image_filename());
		unlink($image->get_thumb_filename());
	}
// }}}
}
add_event_listener(new ImageIO());
?>
