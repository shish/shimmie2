<?php
/*
 * Name: Handle Video
 * Author: velocity37 <velocity37@gmail.com>
 * Modified By: Shish <webmaster@shishnet.org>, jgen <jeffgenovy@gmail.com>, im-mi <im.mi.mail.mi@gmail.com>
 * License: GPLv2
 * Description: Handle FLV, MP4, OGV and WEBM video files.
 * Documentation:
 *  Based heavily on "Handle MP3" by Shish.<br><br>
 *  FLV: Flash player<br>
 *  MP4: HTML5 with Flash fallback<br>
 *  OGV, WEBM: HTML5<br>
 *  MP4's flash fallback is forced with a bit of Javascript as some browsers won't fallback if they can't play H.264.
 *  In the future, it may be necessary to change the user agent checks to reflect the current state of H.264 support.<br><br>
 */

class VideoFileHandler extends DataHandlerExtension {
	public function onInitExt(InitExtEvent $event) {
		global $config;

		if($config->get_int("ext_handle_video_version") < 1) {
			if($ffmpeg = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' ffmpeg')) {
				//ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
				if(is_executable(strtok($ffmpeg, PHP_EOL))) {
					$config->set_default_string('video_thumb_engine', 'ffmpeg');
					$config->set_default_string('thumb_ffmpeg_path',  'ffmpeg');
				}
			} else {
				$config->set_default_string('video_thumb_engine', 'static');
				$config->set_default_string('thumb_ffmpeg_path',  '');
			}

			// By default we generate thumbnails ignoring the aspect ratio of the video file.
			//
			// Why? - This allows Shimmie to work with older versions of FFmpeg by default,
			// rather than completely failing out of the box. If people complain that their
			// thumbnails are distorted, then they can turn this feature on manually later.
			$config->set_default_bool('video_thumb_ignore_aspect_ratio', TRUE);

			$config->set_int("ext_handle_video_version", 1);
			log_info("handle_video", "extension installed");
		}

		$config->set_default_bool('video_playback_autoplay', TRUE);
		$config->set_default_bool('video_playback_loop', TRUE);
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		//global $config;

		$thumbers = array(
			'None'   => 'static',
			'ffmpeg' => 'ffmpeg'
		);

		$sb = new SetupBlock("Video Thumbnail Options");

		$sb->add_choice_option("video_thumb_engine", $thumbers, "Engine: ");

		//if($config->get_string("video_thumb_engine") == "ffmpeg") {
			$sb->add_label("<br>Path to ffmpeg: ");
			$sb->add_text_option("thumb_ffmpeg_path");
		//}

		// Some older versions of ffmpeg have trouble with the automatic aspect ratio scaling.
		// This adds an option in the Board Config to disable the aspect ratio scaling.
		$sb->add_label("<br>");
		$sb->add_bool_option("video_thumb_ignore_aspect_ratio", "Ignore aspect ratio when creating thumbnails: ");

		$event->panel->add_block($sb);
		
		$sb = new SetupBlock("Video Playback Options");
		$sb->add_bool_option("video_playback_autoplay", "Autoplay: ");
		$sb->add_label("<br>");
		$sb->add_bool_option("video_playback_loop", "Loop: ");
		$event->panel->add_block($sb);
	}

	/**
	 * Generate the Thumbnail image for particular file.
	 *
	 * @param string $hash
	 * @return bool Returns true on successful thumbnail creation.
	 */
	protected function create_thumb($hash) {
		global $config;

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
				$ffmpeg = escapeshellcmd($config->get_string("thumb_ffmpeg_path"));

				$w = (int)$config->get_int("thumb_width");
				$h = (int)$config->get_int("thumb_height");
				$inname  = escapeshellarg(warehouse_path("images", $hash));
				$outname = escapeshellarg(warehouse_path("thumbs", $hash));
			
				if ($config->get_bool("video_thumb_ignore_aspect_ratio") == true)
				{
					$cmd = escapeshellcmd("{$ffmpeg} -y -i {$inname} -ss 00:00:00.0 -f image2 -vframes 1 {$outname}");
				}
				else
				{
					$scale = 'scale="' . escapeshellarg("if(gt(a,{$w}/{$h}),{$w},-1)") . ':' . escapeshellarg("if(gt(a,{$w}/{$h}),-1,{$h})") . '"';
					$cmd = "{$ffmpeg} -y -i {$inname} -vf {$scale} -ss 00:00:00.0 -f image2 -vframes 1 {$outname}";
				}

				exec($cmd, $output, $returnValue);

				if ((int)$returnValue == (int)1)
				{
					$ok = true;
				}

				log_debug('handle_video', "Generating thumbnail with command `$cmd`, returns $returnValue");
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
	 * @param mixed[] $metadata
	 * @return Image
	 */
	protected function create_image_from_data($filename, $metadata) {
		$image = new Image();

		//NOTE: No need to set width/height as we don't use it.
		$image->width  = 1;
		$image->height = 1;
		
		switch (mime_content_type($filename)) {
			case "video/webm":
				$image->ext = "webm";
				break;
			case "video/mp4":
				$image->ext = "mp4";
				break;
			case "video/ogg":
				$image->ext = "ogv";
				break;
			case "video/flv":
				$image->ext = "flv";
				break;
			case "video/x-flv":
				$image->ext = "flv";
				break;
		}

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->tag_array = is_array($metadata['tags']) ? $metadata['tags'] : Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	protected function check_contents($file) {
		$success = FALSE;
		if (file_exists($file)) {
			$mimeType = mime_content_type($file);

			$success = in_array($mimeType, [
				'video/webm',
				'video/mp4',
				'video/ogg',
				'video/flv',
				'video/x-flv'
			]);
		}

		return $success;
	}
}

