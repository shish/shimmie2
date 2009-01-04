<?php
/**
 * Name: Flash File Handler
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle Flash files
 */

class FlashFileHandler implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof DataUploadEvent) && $this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);
			if(!move_upload_to_archive($event)) return;
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
			$image = $this->create_image_from_data("images/$ha/$hash", $event->metadata);
			if(is_null($image)) {
				throw new UploadException(
						"Flash Handler failed to create image object from data. ".
						"Note: compressed flash files are currently unsupported");
			}
			send_event(new ImageAdditionEvent($event->user, $image));
		}

		if(($event instanceof ThumbnailGenerationEvent) && $this->supported_ext($event->type)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);
			// FIXME: scale image, as not all boards use 192x192
			copy("ext/handle_flash/thumb.jpg", "thumbs/$ha/$hash");
		}

		if(($event instanceof DisplayingImageEvent) && $this->supported_ext($event->image->ext)) {
			$this->theme->display_image($event->page, $event->image);
		}
	}

	private function supported_ext($ext) {
		$exts = array("swf");
		return array_contains($exts, strtolower($ext));
	}

	private function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = tag_explode($metadata['tags']);
		$image->source    = $metadata['source'];

		// redundant, since getimagesize() works on SWF o_O
//		$rect = $this->swf_get_bounds($filename);
//		if(is_null($rect)) {
//			return $null;
//		}
//		$image->width = $rect[1];
//		$image->height = $rect[3];

		if(!($info = getimagesize($filename))) return null;

		$image->width = $info[0];
		$image->height = $info[1];

		return $image;
	}

	private function str_to_binarray($string) {
		$binary = array();
		for($j=0; $j<strlen($string); $j++) {
			$c = ord($string[$j]);
			for($i=7; $i>=0; $i--) {
				$binary[] = ($c >> $i) & 0x01;
			}
		}
		return $binary;
	}

	private function binarray_to_int($binarray, $start=0, $length=32) {
		$int = 0;
		for($i=$start; $i<$start + $length; $i++) {
			$int = $int << 1;
			$int = $int + ($binarray[$i] == "1" ? 1 : 0);
		}
		return $int;
	}

	private function swf_get_bounds($filename) {
		$fp = fopen($filename, "r");
		$head = fread($fp, 3);
		$version = fread($fp, 1);
		$length = fread($fp, 4);

		if($head == "FWS") {
			$data = fread($fp, 16);
		}
		else if($head == "CWS") {
			$data = fread($fp, 128*1024);
			$data = gzuncompress($data);
			$data = substr($data, 0, 16);
		}

		$bounds = array();
		$rect_bin = $this->str_to_binarray($data);
		$nbits = $this->binarray_to_int($rect_bin, 0, 5);
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 0 * $nbits, $nbits) / 20;
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 1 * $nbits, $nbits) / 20;
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 2 * $nbits, $nbits) / 20;
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 3 * $nbits, $nbits) / 20;

		return $bounds;
	}

	private function check_contents($file) {
		if(!file_exists($file)) return false;

		$fp = fopen($file, "r");
		$head = fread($fp, 3);
		fclose($fp);
		if(!array_contains(array("CWS", "FWS"), $head)) return false;

		return true;
	}
}
add_event_listener(new FlashFileHandler());
?>
