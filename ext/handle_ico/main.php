<?php
/*
 * Name: Handle ICO
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle windows icons
 */

class IcoFileHandler extends Extension {
	public function onDataUpload(DataUploadEvent $event) {
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);
			move_upload_to_archive($event);
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
			$image = $this->create_image_from_data("images/$ha/$hash", $event->metadata);
			if(is_null($image)) {
				throw new UploadException("Icon handler failed to create image object from data");
			}
			$iae = new ImageAdditionEvent($image);
			send_event($iae);
			$event->image_id = $iae->image->id;
		}
	}

	public function onThumbnailGeneration(ThumbnailGenerationEvent $event) {
		if($this->supported_ext($event->type)) {
			$this->create_thumb($event->hash);
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $page;
		if($this->supported_ext($event->image->ext)) {
			$this->theme->display_image($page, $event->image);
		}
	}

	/**
	 * @param string $ext
	 * @return bool
	 */
	private function supported_ext($ext) {
		$exts = array("ico", "ani", "cur");
		return in_array(strtolower($ext), $exts);
	}

	/**
	 * @param string $filename
	 * @param mixed[] $metadata
	 * @return Image
	 */
	private function create_image_from_data($filename, $metadata) {
		$image = new Image();

		$fp = fopen($filename, "r");
		$header = unpack("Snull/Stype/Scount", fread($fp, 6));

		$subheader = unpack("Cwidth/Cheight/Ccolours/Cnull/Splanes/Sbpp/Lsize/loffset", fread($fp, 16));
		fclose($fp);

		$width = $subheader['width'];
		$height = $subheader['height'];
		$image->width = $width == 0 ? 256 : $width;
		$image->height = $height == 0 ? 256 : $height;

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = $metadata['tags'];
		$image->source    = $metadata['source'];

		return $image;
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	private function check_contents($file) {
		if(!file_exists($file)) return false;
		$fp = fopen($file, "r");
		$header = unpack("Snull/Stype/Scount", fread($fp, 6));
		fclose($fp);
		return ($header['null'] == 0 && ($header['type'] == 0 || $header['type'] == 1));
	}

	/**
	 * @param string $hash
	 * @return bool
	 */
	private function create_thumb($hash) {
		global $config;

		$inname  = warehouse_path("images", $hash);
		$outname = warehouse_path("thumbs", $hash);

		$w = $config->get_int("thumb_width");
		$h = $config->get_int("thumb_height");
		$q = $config->get_int("thumb_quality");
		$mem = $config->get_int("thumb_mem_limit") / 1024 / 1024; // IM takes memory in MB

		if($config->get_bool("ico_convert")) {
			// "-limit memory $mem" broken?
			exec("convert {$inname}[0] -geometry {$w}x{$h} -quality {$q} jpg:$outname");
		}
		else {
			copy($inname, $outname);
		}

		return true;
	}
}

