<?php

class RegenThumb extends Extension {
// event handler {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "regen_thumb")) {
			global $user;
			if($user->is_admin() && isset($_POST['program']) && isset($_POST['image_id'])) {
				$this->make_thumb($_POST['program'], $_POST['image_id']);
			}
		}

		if(is_a($event, 'DisplayingImageEvent')) {
			global $page;
			global $user;
			if($user->is_admin()) {
				$page->add_side_block(new Block("Regen Thumb", $this->build_regen_buttons($event->image)));
			}
		}
	}
// }}}
// do things {{{
	// FIXME: make locations of convert / epeg config variables
	private function make_thumb($program, $image_id) {
		global $database;
		global $config;

		$i_image_id = int_escape($image_id);
		$image = $database->get_image($i_image_id);
		
		$f_image = $this->check_filename($image->get_image_filename());
		$f_thumb = $this->check_filename($image->get_thumb_filename());

		$w = $config->get_int('thumb_width');
		$h = $config->get_int('thumb_height');
		$q = $config->get_int('thumb_quality');

		switch($program) {
			case 'convert':
				unlink($f_thumb);
				exec("convert $f_image -geometry {$w}x{$h} -quality {$q} $f_thumb");
				break;
			case 'gd':
				$this->make_thumb_gd($f_image, $f_thumb);
				break;
			default:
				break;
		}

		global $page;
		$page->set_title("Thumbnail Regenerated");
		$page->set_heading("Thumbnail Regenerated");
		$page->add_side_block(new NavBlock());
		$page->add_main_block(new Block("Thumbnail", $this->build_thumb_html($image)));
	}

	private function build_thumb_html($image) {
		$link = make_link("post/view/".$image->id);
		$img = $image->get_thumb_link();
		$html = "<a href='$link'><img src='$img'></a>";
		return $html;
	}

	private function check_filename($filename) {
		$filename = preg_replace("#[^a-zA-Z0-9/\._]#", "", $filename);
		return $filename;
	}

// }}} 
// GD thumber {{{
	private function read_file($fname) {
		$fp = fopen($fname, "r");
		if(!$fp) return false;

		$data = fread($fp, filesize($fname));
		fclose($fp);

		return $data;
	}
	private function make_thumb_gd($inname, $outname) {
		global $config;
		$thumb = $this->get_thumb($inname);
		return imagejpeg($thumb, $outname, $config->get_int('thumb_quality'));
	}

	private function get_memory_limit() {
		global $config;

		// thumbnail generation requires lots of memory
		$default_limit = 8*1024*1024;
		$shimmie_limit = parse_shorthand_int($config->get_int("thumb_gd_mem_limit"));
		if($shimmie_limit < 3*1024*1024) {
			// we aren't going to fit, override
			$shimmie_limit = $default_limit;
		}
		
		ini_set("memory_limit", $shimmie_limit);
		$memory = parse_shorthand_int(ini_get("memory_limit"));

		// changing of memory limit is disabled / failed
		if($memory == -1) {
			$memory = $default_limit; 
		}

		return $memory;
	}

	private function get_thumb($tmpname) {
		global $config;

		$info = getimagesize($tmpname);
		$width = $info[0];
		$height = $info[1];

		$max_width  = $config->get_int('thumb_width');
		$max_height = $config->get_int('thumb_height');

		$memory_use = (filesize($tmpname)*2) + ($width*$height*4) + (4*1024*1024);
		$memory_limit = $this->get_memory_limit();
		
		if($memory_use > $memory_limit) {
			$thumb = imagecreatetruecolor($max_width, min($max_height, 64));
			$white = imagecolorallocate($thumb, 255, 255, 255);
			$black = imagecolorallocate($thumb, 0,   0,   0);
			imagefill($thumb, 0, 0, $white);
			imagestring($thumb, 5, 10, 24, "Image Too Large :(", $black);
			return $thumb;
		}
		else {
			$image = imagecreatefromstring($this->read_file($tmpname));

			$xscale = ($max_height / $height);
			$yscale = ($max_width / $width);
			$scale = ($xscale < $yscale) ? $xscale : $yscale;

			if($scale >= 1) {
				$thumb = $image;
			}
			else {
				$thumb = imagecreatetruecolor($width*$scale, $height*$scale);
				imagecopyresampled(
						$thumb, $image, 0, 0, 0, 0,
						$width*$scale, $height*$scale, $width, $height
						);
			}
			return $thumb;
		}
	}
// }}}
// page building {{{
	private function build_regen_buttons($image) {
		global $user;
		if($user->is_admin()) {
			return "
				<form action='".make_link("regen_thumb")."' method='POST'>
				<input type='hidden' name='image_id' value='{$image->id}'>
				<select name='program'>
					<option value='convert'>ImageMagick</option>
					<option value='gd'>GD</option>
					<!-- <option value='epeg'>EPEG (for JPEG only)</option> -->
				</select>
				<input type='submit' value='Regenerate'>
				</form>
			";
		}
	}
// }}}
}
add_event_listener(new RegenThumb());
?>
