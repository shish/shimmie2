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

class VideoFileHandler extends DataHandlerExtension
{
    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        if ($config->get_int("ext_handle_video_version") < 1) {
            if ($ffmpeg = shell_exec((PHP_OS == 'WINNT' ? 'where' : 'which') . ' ffmpeg')) {
                //ffmpeg exists in PATH, check if it's executable, and if so, default to it instead of static
                if (is_executable(strtok($ffmpeg, PHP_EOL))) {
                    $config->set_default_string('thumb_ffmpeg_path', 'ffmpeg');
                }
            } else {
                $config->set_default_string('thumb_ffmpeg_path', '');
            }

            $config->set_int("ext_handle_video_version", 1);
            log_info("handle_video", "extension installed");
        }

        $config->set_default_bool('video_playback_autoplay', true);
        $config->set_default_bool('video_playback_loop', true);
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Video Options");
        $sb->add_label("<br>Path to ffmpeg: ");
        $sb->add_text_option("thumb_ffmpeg_path");
        $sb->add_label("<br>");
        $sb->add_bool_option("video_playback_autoplay", "Autoplay: ");
        $sb->add_label("<br>");
        $sb->add_bool_option("video_playback_loop", "Loop: ");
        $event->panel->add_block($sb);
    }

    /**
     * Generate the Thumbnail image for particular file.
     */
    protected function create_thumb(string $hash): bool
    {
        global $config;

        $ok = false;

        $ffmpeg = $config->get_string("thumb_ffmpeg_path");
        $inname  = warehouse_path("images", $hash);
        $outname = warehouse_path("thumbs", $hash);

        $orig_size = $this->video_size($inname);
        $scaled_size = get_thumbnail_size_scaled($orig_size[0], $orig_size[1]);
        $cmd = escapeshellcmd(implode(" ", [
            escapeshellarg($ffmpeg),
            "-y", "-i", escapeshellarg($inname),
            "-vf", "scale={$scaled_size[0]}:{$scaled_size[1]}",
            "-ss", "00:00:00.0",
            "-f", "image2",
            "-vframes", "1",
            escapeshellarg($outname),
        ]));

        exec($cmd, $output, $ret);

        if ((int)$ret == (int)0) {
            $ok = true;
            log_error('handle_video', "Generating thumbnail with command `$cmd`, returns $ret");
        } else {
            log_debug('handle_video', "Generating thumbnail with command `$cmd`, returns $ret");
        }

        return $ok;
    }

    protected function video_size(string $filename)
    {
        global $config;
        $ffmpeg = $config->get_string("thumb_ffmpeg_path");
        $cmd = escapeshellcmd(implode(" ", [
            escapeshellarg($ffmpeg),
            "-y", "-i", escapeshellarg($filename),
            "-vstats"
        ]));
        $output = shell_exec($cmd . " 2>&1");
        // error_log("Getting size with `$cmd`");

        $regex_sizes = "/Video: .* ([0-9]{1,4})x([0-9]{1,4})/";
        if (preg_match($regex_sizes, $output, $regs)) {
            if (preg_match("/displaymatrix: rotation of (90|270).00 degrees/", $output)) {
                $size = [$regs[2], $regs[1]];
            } else {
                $size = [$regs[1], $regs[2]];
            }
        } else {
            $size = [1, 1];
        }
        log_debug('handle_video', "Getting video size with `$cmd`, returns $output -- $size[0], $size[1]");
        return $size;
    }

    protected function supported_ext(string $ext): bool
    {
        $exts = ["flv", "mp4", "m4v", "ogv", "webm"];
        return in_array(strtolower($ext), $exts);
    }

    protected function create_image_from_data(string $filename, array $metadata): Image
    {
        $image = new Image();

        //NOTE: No need to set width/height as we don't use it.
        $size = $this->video_size($filename);
        $image->width  = $size[0];
        $image->height = $size[1];
        
        switch (getMimeType($filename)) {
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

    protected function check_contents(string $tmpname): bool
    {
        $success = false;
        if (file_exists($tmpname)) {
            $mimeType = getMimeType($tmpname);

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
