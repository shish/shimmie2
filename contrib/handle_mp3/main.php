<?php
/*
 * Name: Handle MP3
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle MP3 files
 */

class MP3FileHandler extends DataHandlerExtension {
	protected function create_thumb($hash) {
		// FIXME: scale image, as not all boards use 192x192
		copy("ext/handle_mp3/thumb.jpg", warehouse_path("thumbs", $hash));
	}

	protected function supported_ext($ext) {
		$exts = array("mp3");
		return in_array(strtolower($ext), $exts);
	}

	protected function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		// FIXME: need more flash format specs :|
		$image->width = 0;
		$image->height = 0;

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	protected function check_contents($file) {
		// FIXME: mp3 magic header?
		return (file_exists($file));
	}
}
add_event_listener(new MP3FileHandler());
?>
