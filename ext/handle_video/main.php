<?php
/*
 * Name: Handle Video
 * Author: velocity37 <velocity37@gmail.com>
 * Modified By: Shish <webmaster@shishnet.org>, jgen <jeffgenovy@gmail.com>
 * License: GPLv2
 * Description: Handle FLV, MP4, OGV and WEBM video files.
 * Documentation:
 *  Based heavily on "Handle MP3" by Shish.<br><br>
 *  FLV: Flash player<br>
 *  MP4: HTML5 with Flash fallback<br>
 *  OGV, WEBM: HTML5<br>
 *  MP4's flash fallback is forced with a bit of Javascript as some browsers won't fallback if they can't play H.264.
 *  In the future, it may be necessary to change the user agent checks to reflect the current state of H.264 support.<br><br>
 *  Made possible by:<br>
 *  <a href='http://getid3.sourceforge.net/'>getID3()</a> - Gets media information with PHP (no bulky FFMPEG API required).<br>
 *  <a href='http://jarisflvplayer.org/'>Jaris FLV Player</a> - GPLv3 flash multimedia player.
 */

class VideoFileHandler extends DataHandlerExtension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string('video_thumb_engine', 'static');
		$config->set_default_string('thumb_ffmpeg_path', '');
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		global $config;

		$thumbers = array();
		$thumbers['None'] = "static";
		$thumbers['ffmpeg'] = "ffmpeg";

		$sb = new SetupBlock("Video Thumbnail Options");

		$sb->add_choice_option("video_thumb_engine", $thumbers, "Engine: ");

		//if($config->get_string("video_thumb_engine") == "ffmpeg") {
			$sb->add_label("<br>Path to ffmpeg: ");
			$sb->add_text_option("thumb_ffmpeg_path");
		//}
		$event->panel->add_block($sb);
	}

	/**
	 * @param string $hash
	 * @return bool
	 */
	protected function create_thumb($hash) {
		global $config;

		// this is never used...
		//$q = $config->get_int("thumb_quality");

		$ok = false;

		switch($config->get_string("video_thumb_engine"))
		{
			default:
			case 'static':
				$outname = warehouse_path("thumbs", $hash);
				copy("ext/handle_video/thumb.jpg", $outname);
				$ok = true;
				break;
			case 'ffmpeg':
				$ffmpeg = escapeshellarg($config->get_string("thumb_ffmpeg_path"));

				$w = (int)$config->get_int("thumb_width");
				$h = (int)$config->get_int("thumb_height");
				$inname  = escapeshellarg(warehouse_path("images", $hash));
				$outname = escapeshellarg(warehouse_path("thumbs", $hash));

				$cmd = escapeshellcmd("{$ffmpeg} -i {$inname} -s {$w}x{$h} -ss 00:00:00.0 -f image2 -vframes 1 {$outname}");
				exec($cmd, $output, $ret);

				// TODO: We should really check the result of the exec to see if it really succeeded.
				$ok = true;

				log_debug('handle_video', "Generating thumbnail with command `$cmd`, returns $ret");
				break;
		}

		return $ok;
	}

	/**
	 * @param string $ext
	 * @return bool
	 */
	protected function supported_ext($ext) {
		$exts = array("flv", "mp4", "m4v", "ogv", "webm");
		return in_array(strtolower($ext), $exts);
	}

	/**
	 * @param string $filename
	 * @param array $metadata
	 * @return Image|null
	 */
	protected function create_image_from_data($filename, $metadata) {
		//global $config;

		$image = new Image();

		require_once('lib/getid3/getid3/getid3.php');
		$getID3 = new getID3;
		$ThisFileInfo = $getID3->analyze($filename);
		
		if (isset($ThisFileInfo['video']['resolution_x']) && isset($ThisFileInfo['video']['resolution_y'])) {
			$image->width = $ThisFileInfo['video']['resolution_x'];
			$image->height = $ThisFileInfo['video']['resolution_y'];
		} else {
			$image->width = 0;
			$image->height = 0;
		}
		
		switch ($ThisFileInfo['mime_type']) {
			case "video/webm":
				$image->ext = "webm";
				break;
			case "video/quicktime":
				$image->ext = "mp4";
				break;
			case "application/ogg":
				$image->ext = "ogv";
				break;
			case "video/x-flv":
				$image->ext = "flv";
				break;
		}

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
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
			$ThisFileInfo = $getID3->analyze($file);
			if (isset($ThisFileInfo['mime_type']) && ($ThisFileInfo['mime_type'] == "video/webm" || $ThisFileInfo['mime_type'] == "video/quicktime" || $ThisFileInfo['mime_type'] == "application/ogg" || $ThisFileInfo['mime_type'] == 'video/x-flv')) {
				return TRUE;
			}
		}
			return FALSE;
	}
}

