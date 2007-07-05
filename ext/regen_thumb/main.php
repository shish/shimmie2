<?php

class RegenThumb extends Extension {
	var $theme;
// event handler {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("regen_thumb", "RegenThumbTheme");

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
				$this->theme->display_buttons($page, $event->image->id);
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
				if(file_exists($f_thumb)) unlink($f_thumb);
				$mem = $config->get_int("thumb_max_memory") / 1024 / 1024; // IM takes memory in MB
				exec("convert {$f_image}[0] -limit memory {$mem} -geometry {$w}x{$h} -quality {$q} jpg:$f_thumb");
				break;
			case 'gd':
				$this->make_thumb_gd($f_image, $f_thumb);
				break;
			default:
				break;
		}

		global $page;
		$this->theme->display_results($page, $image);
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
}
add_event_listener(new RegenThumb());
?>
