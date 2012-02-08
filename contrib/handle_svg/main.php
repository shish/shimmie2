<?php
/*
 * Name: Handle SVG
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle SVG files
 */

class SVGFileHandler extends SimpleExtension {
	public function onDataUpload(DataUploadEvent $event) {
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);
			if(!move_upload_to_archive($event)) return;
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
			$image = $this->create_image_from_data(warehouse_path("images", $hash), $event->metadata);
			if(is_null($image)) {
				throw new UploadException("SVG handler failed to create image object from data");
			}
			$iae = new ImageAdditionEvent($event->user, $image);
			send_event($iae);
			$event->image_id = $iae->image->id;
		}
	}

	public function onThumbnailGeneration(ThumbnailGenerationEvent $event) {
		global $config;
		if($this->supported_ext($event->type)) {
			$hash = $event->hash;
			$ha = substr($hash, 0, 2);

			copy("ext/handle_svg/thumb.jpg", warehouse_path("thumbs", $hash));
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $page;
		if($this->supported_ext($event->image->ext)) {
			$this->theme->display_image($page, $event->image);
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $page;
		if($event->page_matches("get_svg")) {
			$id = int_escape($event->get_arg(0));
			$image = Image::by_id($id);
			$hash = $image->hash;

			$page->set_type("image/svg+xml");
			$page->set_mode("data");
			$page->set_data(file_get_contents(warehouse_path("images", $hash)));
		}
	}

	private function supported_ext($ext) {
		$exts = array("svg");
		return in_array(strtolower($ext), $exts);
	}

	private function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		$msp = new MiniSVGParser($filename);
		$image->width = $msp->width;
		$image->height = $msp->height;

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

		$msp = new MiniSVGParser($file);
		return $msp->valid;
	}
}

class MiniSVGParser {
	var $valid=false, $width=0, $height=0;

	function MiniSVGParser($file) {
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, "startElement"), array($this, "endElement"));
		$this->valid = xml_parse($xml_parser, file_get_contents($file), true);
		xml_parser_free($xml_parser);
	}

	function startElement($parser, $name, $attrs) {
		if($name == "SVG") {
			$this->width = int_escape($attrs["WIDTH"]);
			$this->height = int_escape($attrs["HEIGHT"]);
		}
	}

	function endElement($parser, $name) {
	}
}
?>
