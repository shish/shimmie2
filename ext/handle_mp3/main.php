<?php
/*
 * Name: Handle MP3
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle MP3 files
 */

class MP3FileHandler extends DataHandlerExtension {
	/**
	 * @param string $hash
	 * @return bool
	 */
	protected function create_thumb($hash) {
		copy("ext/handle_mp3/thumb.jpg", warehouse_path("thumbs", $hash));
		return true;
	}

	/**
	 * @param string $ext
	 * @return bool
	 */
	protected function supported_ext($ext) {
		$exts = array("mp3");
		return in_array(strtolower($ext), $exts);
	}

	/**
	 * @param string $filename
	 * @param mixed[] $metadata
	 * @return Image|null
	 */
	protected function create_image_from_data($filename, $metadata) {
		$image = new Image();

		//NOTE: No need to set width/height as we don't use it.
		$image->width  = 1;
		$image->height = 1;

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];

		//Filename is renamed to "artist - title.mp3" when the user requests download by using the download attribute & jsmediatags.js
		$image->filename = $metadata['filename'];

		$image->ext       = $metadata['extension'];
		$image->tag_array = is_array($metadata['tags']) ? $metadata['tags'] : Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	/**
	 * @param $file
	 * @return bool
	 */
	protected function check_contents($file) {
		$success = FALSE;

		if (file_exists($file)) {
			$mimeType = mime_content_type($file);

			$success = ($mimeType == 'audio/mpeg');
		}

		return $success;
	}
}

