<?php
/**
 * Name: Handle Pixel
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle JPEG, PNG, GIF, etc files
 */

class PixelFileHandler extends DataHandlerExtension {
	protected function supported_ext($ext) {
		$exts = array("jpg", "jpeg", "gif", "png");
		return in_array(strtolower($ext), $exts);
	}

	protected function create_image_from_data(/*string*/ $filename, /*array*/ $metadata) {
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

	protected function check_contents(/*string*/ $file) {
		$valid = Array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG);
		if(!file_exists($file)) return false;
		$info = getimagesize($file);
		if(is_null($info)) return false;
		if(in_array($info[2], $valid)) return true;
		return false;
	}

	protected function create_thumb(/*string*/ $hash) {
		$outname = warehouse_path("thumbs", $hash);
		if(file_exists($outname)) {
			return true;
		}
		return $this->create_thumb_force($hash);
	}

	protected function create_thumb_force(/*string*/ $hash) {
		$inname  = warehouse_path("images", $hash);
		$outname = warehouse_path("thumbs", $hash);
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
	
	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		$event->add_part("
			<form>
				<select id='zoomer'>
					<option value='full'>Full Size</option>
					<option value='width'>Fit Width</option>
					<option value='height'>Fit Height</option>
					<option value='both'>Fit Both</option>
				</select>
			</form>
		", 20);
	}

// IM thumber {{{
	private function make_thumb_convert(/*string*/ $inname, /*string*/ $outname) {
		global $config;

		$w = $config->get_int("thumb_width");
		$h = $config->get_int("thumb_height");
		$q = $config->get_int("thumb_quality");

		// Windows is a special case
		if(in_array("OS", $_SERVER) && $_SERVER["OS"] == 'Windows_NT') {
			$convert = $config->get_string("thumb_convert_path");
		}
		else {
			$convert = "convert";
		}

		//  ffff imagemagic fails sometimes, not sure why
		//$format = "'%s' '%s[0]' -format '%%[fx:w] %%[fx:h]' info:";
		//$cmd = sprintf($format, $convert, $inname);
		//$size = shell_exec($cmd);
		//$size = explode(" ", trim($size));
		$size = getimagesize($inname);
		if($size[0] > $size[1]*5) $size[0] = $size[1]*5;
		if($size[1] > $size[0]*5) $size[1] = $size[0]*5;

		// running the call with cmd.exe requires quoting for our paths
		$format = '"%s" "%s[0]" -extent %ux%u -flatten -strip -thumbnail %ux%u -quality %u jpg:"%s"';
		$cmd = sprintf($format, $convert, $inname, $size[0], $size[1], $w, $h, $q, $outname);
		$cmd = str_replace("\"convert\"", "convert", $cmd); // quotes are only needed if the path to convert contains a space; some other times, quotes break things, see github bug #27
		exec($cmd, $output, $ret);

		log_debug('handle_pixel', "Generating thumnail with command `$cmd`, returns $ret");

		if($config->get_bool("thumb_optim", false)) {
			exec("jpegoptim $outname", $output, $ret);
		}

		return true;
	}
// }}}
// epeg thumber {{{
	private function make_thumb_epeg(/*string*/ $inname, /*string*/ $outname) {
		global $config;
		$w = $config->get_int("thumb_width");
		exec("epeg $inname -c 'Created by EPEG' --max $w $outname");
		return true;
	}
	// }}}
// GD thumber {{{
	private function make_thumb_gd(/*string*/ $inname, /*string*/ $outname) {
		global $config;
		$thumb = $this->get_thumb($inname);
		$ok = imagejpeg($thumb, $outname, $config->get_int('thumb_quality'));
		imagedestroy($thumb);
		return $ok;
	}

	private function get_thumb(/*string*/ $tmpname) {
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
			if($width > $height*5) $width = $height*5;
			if($height > $width*5) $height = $width*5;

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

	private function read_file(/*string*/ $fname) {
		$fp = fopen($fname, "r");
		if(!$fp) return false;

		$data = fread($fp, filesize($fname));
		fclose($fp);

		return $data;
	}
// }}}
}
?>
