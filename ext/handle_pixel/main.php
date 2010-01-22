<?php
/**
 * Name: Handle Pixel
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle JPEG, PNG, GIF, etc files
 */

class PixelFileHandler extends DataHandlerExtension {
	protected function supported_ext($ext) {
		$exts = array("jpg", "jpeg", "gif", "png");
		return in_array(strtolower($ext), $exts);
	}

	protected function create_image_from_data($filename, $metadata) {
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
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	protected function check_contents($file) {
		$valid = Array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG);
		if(!file_exists($file)) return false;
		$info = getimagesize($file);
		if(is_null($info)) return false;
		if(in_array($info[2], $valid)) return true;
		return false;
	}

	protected function create_thumb($hash) {
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

		// convert to bitmap & back to strip metadata -- otherwise we
		// can end up with 3KB of jpg data and 200KB of misc extra...
		// "-limit memory $mem" broken?

		// Windows is a special case, use what will work on most everything else first
		if(in_array("OS", $_SERVER) && $_SERVER["OS"] != 'Windows_NT') {
			$cmd = "convert {$inname}[0] -strip -thumbnail {$w}x{$h} jpg:$outname";
		}
		else {
			$imageMagick = $config->get_string("thumb_convert_path");

			// running the call with cmd.exe requires quoting for our paths
			$stringFormat = '"%s" "%s[0]" -strip -thumbnail %ux%u jpg:"%s"';

			// Concat the command altogether
			$cmd = sprintf($stringFormat, $imageMagick, $inname, $w, $h, $outname);
		}

		// Execute IM's convert command, grab the output and return code it'll help debug it
		exec($cmd, $output, $ret);

		log_debug('handle_pixel', "Generating thumnail with command `$cmd`, returns $ret");

		return true;
	}
// }}}
// epeg thumber {{{
	private function make_thumb_epeg($inname, $outname) {
		global $config;
		$w = $config->get_int("thumb_width");
		exec("epeg $inname -c 'Created by EPEG' --max $w $outname");
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
