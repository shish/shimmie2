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
		global $config;

		$image = new Image();

		// FIXME: need more flash format specs :|
		$image->width = 0;
		$image->height = 0;

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		
		//Cheat by using the filename to store artist/title if available
		require_once('lib/getid3/getid3/getid3.php');
		$getID3 = new getID3;
		$ThisFileInfo = $getID3->analyze($filename, TRUE);
		
		if (isset($ThisFileInfo['tags']['id3v2']['artist'][0]) && isset($ThisFileInfo['tags']['id3v2']['title'][0])) {
			$image->filename = $ThisFileInfo['tags']['id3v2']['artist'][0]." - ".$ThisFileInfo['tags']['id3v2']['title'][0].".mp3";
		} else if (isset($ThisFileInfo['tags']['id3v1']['artist'][0]) && isset($ThisFileInfo['tags']['id3v1']['title'][0])) {
			$image->filename = $ThisFileInfo['tags']['id3v1']['artist'][0]." - ".$ThisFileInfo['tags']['id3v1']['title'][0].".mp3";
		} else {
			$image->filename = $metadata['filename'];
		}
		
		$image->ext       = $metadata['extension'];
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	/**
	 * @param $file
	 * @return bool
	 */
	protected function check_contents($file) {
		if (file_exists($file)) {
			require_once('lib/getid3/getid3/getid3.php');
			$getID3 = new getID3;
			$ThisFileInfo = $getID3->analyze($file, TRUE);
			if (isset($ThisFileInfo['fileformat']) && $ThisFileInfo['fileformat'] == "mp3") {
				return TRUE;
			}
		}
		return FALSE;
	}
}

