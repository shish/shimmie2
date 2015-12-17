<?php
/*
 * Name: Handle SVG
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle SVG files. (No thumbnail is generated for SVG files)
 */

class SVGFileHandler extends Extension {
	public function onDataUpload(DataUploadEvent $event) {
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
			$hash = $event->hash;
			if(!move_upload_to_archive($event)) return;
			send_event(new ThumbnailGenerationEvent($event->hash, $event->type));
			$image = $this->create_image_from_data(warehouse_path("images", $hash), $event->metadata);
			if(is_null($image)) {
				throw new UploadException("SVG handler failed to create image object from data");
			}
			$iae = new ImageAdditionEvent($image);
			send_event($iae);
			$event->image_id = $iae->image->id;
		}
	}

	public function onThumbnailGeneration(ThumbnailGenerationEvent $event) {
		if($this->supported_ext($event->type)) {
			$hash = $event->hash;

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
		global $page;
		if($event->page_matches("get_svg")) {
			$id = int_escape($event->get_arg(0));
			$image = Image::by_id($id);
			$hash = $image->hash;

			$page->set_type("image/svg+xml");
			$page->set_mode("data");
			$page->set_data(file_get_contents(warehouse_path("images", $hash)));
		}
	}

	/**
	 * @param string $ext
	 * @return bool
	 */
	private function supported_ext($ext) {
		$exts = array("svg");
		return in_array(strtolower($ext), $exts);
	}

	/**
	 * @param string $filename
	 * @param mixed[] $metadata
	 * @return Image
	 */
	private function create_image_from_data($filename, $metadata) {
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

	/**
	 * @param string $file
	 * @return bool
	 */
	private function check_contents($file) {
		if(!file_exists($file)) return false;

		$msp = new MiniSVGParser($file);
		return bool_escape($msp->valid);
	}
}

class MiniSVGParser {
	/** @var bool */
	public $valid=false;
	/** @var int */
	public $width=0;
	/** @var int */
	public $height=0;

	function __construct($file) {
		$xml_parser = xml_parser_create();
		xml_set_element_handler($xml_parser, array($this, "startElement"), array($this, "endElement"));
		$this->valid = bool_escape(xml_parse($xml_parser, file_get_contents($file), true));
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

