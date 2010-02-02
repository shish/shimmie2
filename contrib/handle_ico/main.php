<?php
/*
 * Name: Handle ICO
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle windows icons
 */

class IcoFileHandler extends SimpleExtension {
	public function onDataUpload($event) {
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);
			if(!move_upload_to_archive($event)) return;
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
			$image = $this->create_image_from_data("images/$ha/$hash", $event->metadata);
			if(is_null($image)) {
				throw new UploadException("Icon handler failed to create image object from data");
			}
			$iae = new ImageAdditionEvent($event->user, $image);
			send_event($iae);
			$event->image_id = $iae->image->id;
		}
	}

	public function onThumbnailGeneration($event) {
		if($this->supported_ext($event->type)) {
			$this->create_thumb($event->hash);
		}
	}

	public function onDisplayingImage($event) {
		global $page;
		if($this->supported_ext($event->image->ext)) {
			$this->theme->display_image($page, $event->image);
		}
	}

	public function onPageRequest($event) {
		global $config, $database, $page;
		if($event->page_matches("get_ico")) {
			$id = int_escape($event->get_arg(0));
			$image = Image::by_id($id);
			$hash = $image->hash;
			$ha = substr($hash, 0, 2);

			$page->set_type("image/x-icon");
			$page->set_mode("data");
			$page->set_data(file_get_contents("images/$ha/$hash"));
		}
	}


	private function supported_ext($ext) {
		$exts = array("ico", "ani", "cur");
		return in_array(strtolower($ext), $exts);
	}

	private function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		$info = "";
		$fp = fopen($filename, "r");
		$header = unpack("snull/stype/scount", fread($fp, 6));

		$subheader = unpack("cwidth/cheight/ccolours/cnull/splanes/sbpp/lsize/loffset", fread($fp, 16));
		fclose($fp);

		$image->width = $subheader['width'];
		$image->height = $subheader['height'];

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	private function check_contents($file) {
		if(!file_exists($file)) return false;
		$fp = fopen($file, "r");
		$header = unpack("snull/stype/scount", fread($fp, 6));
		fclose($fp);
		return ($header['null'] == 0 && ($header['type'] == 0 || $header['type'] == 1));
	}

	private function create_thumb($hash) {
		global $config;

		$inname  = warehouse_path("images", $hash);
		$outname = warehouse_path("thumbs", $hash);

		$w = $config->get_int("thumb_width");
		$h = $config->get_int("thumb_height");
		$q = $config->get_int("thumb_quality");
		$mem = $config->get_int("thumb_max_memory") / 1024 / 1024; // IM takes memory in MB

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
?>
