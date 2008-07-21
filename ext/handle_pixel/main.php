<?php
/**
 * Name: Pixel File Handler
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle JPG, PNG, GIF, etc files
 */

class PixelFileHandler extends Extension {
	var $theme;

	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("handle_pixel", "PixelFileHandlerTheme");

		if(is_a($event, 'DataUploadEvent') && $this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);
			if(!move_upload_to_archive($event)) return;
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
			$image = $this->create_image_from_data("images/$ha/$hash", $event->metadata);
			if(is_null($image)) {
				$event->veto("Pixel Handler failed to create image object from data");
				return;
			}

			$iae = new ImageAdditionEvent($event->user, $image);
			send_event($iae);
			if($iae->vetoed) {
				$event->veto($iae->veto_reason);
				return;
			}
		}

		if(is_a($event, 'ThumbnailGenerationEvent') && $this->supported_ext($event->type)) {
			$this->create_thumb($event->hash);
		}

		if(is_a($event, 'DisplayingImageEvent') && $this->supported_ext($event->image->ext)) {
			$this->theme->display_image($event->page, $event->image);
		}
	}

	private function supported_ext($ext) {
		$exts = array("jpg", "jpeg", "gif", "png");
		return array_contains($exts, strtolower($ext));
	}
	
	private function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		$info = "";
		if(!($info = getimagesize($filename))) return null;

		$image->width = $info[0];
		$image->height = $info[1];
		
		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = tag_explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	private function check_contents($file) {
		$valid = Array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG);
		if(!file_exists($file)) return false;
		$info = getimagesize($file);
		if(is_null($info)) return false;
		if(array_contains($valid, $info[2])) return true;
		return false;
	}

	private function create_thumb($hash) {
		$ha = substr($hash, 0, 2);
		$inname  = "images/$ha/$hash";
		$outname = "thumbs/$ha/$hash";
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
		$ok = imagejpeg($thumb, $outname, $config->get_int('thumb_quality'));
		imagedestroy($thumb);
		return $ok;
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

	private function read_file($fname) {
		$fp = fopen($fname, "r");
		if(!$fp) return false;

		$data = fread($fp, filesize($fname));
		fclose($fp);

		return $data;
	}
// }}}
}
add_event_listener(new PixelFileHandler());
?>
