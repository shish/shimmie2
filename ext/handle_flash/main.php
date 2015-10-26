<?php
/*
 * Name: Handle Flash
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle Flash files. (No thumbnail is generated for flash files)
 */

class FlashFileHandler extends DataHandlerExtension {
	/**
	 * @param string $hash
	 * @return bool
	 */
	protected function create_thumb($hash) {
		copy("ext/handle_flash/thumb.jpg", warehouse_path("thumbs", $hash));
		return true;
	}

	/**
	 * @param string $ext
	 * @return bool
	 */
	protected function supported_ext($ext) {
		$exts = array("swf");
		return in_array(strtolower($ext), $exts);
	}

	/**
	 * @param string $filename
	 * @param array $metadata
	 * @return Image|null
	 */
	protected function create_image_from_data(/*string*/ $filename, /*array*/ $metadata) {
		$image = new Image();

		$image->filesize  = $metadata['size'];
		$image->hash	  = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		$info = getimagesize($filename);
		if(!$info) return null;

		$image->width = $info[0];
		$image->height = $info[1];

		return $image;
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	protected function check_contents(/*string*/ $file) {
		if (!file_exists($file)) return false;

		$fp = fopen($file, "r");
		$head = fread($fp, 3);
		fclose($fp);
		if (!in_array($head, array("CWS", "FWS"))) return false;

		return true;
	}
}

