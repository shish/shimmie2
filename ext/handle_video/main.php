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
    const SUPPORTED_MIME = [
        'video/webm',
        'video/mp4',
        'video/ogg',
        'video/flv',
        'video/x-flv'
    ];
    const SUPPORTED_EXT = ["flv", "mp4", "m4v", "ogv", "webm"];

    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        if ($config->get_int("ext_handle_video_version") < 1) {
            // This used to set the ffmpeg path. It does not do this anymore, that is now in the base graphic extension.
            $config->set_int("ext_handle_video_version", 1);
            log_info("handle_video", "extension installed");
        }

        $config->set_default_bool('video_playback_autoplay', true);
        $config->set_default_bool('video_playback_loop', true);
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Video Options");
        $sb->add_bool_option("video_playback_autoplay", "Autoplay: ");
        $sb->add_label("<br>");
        $sb->add_bool_option("video_playback_loop", "Loop: ");
        $event->panel->add_block($sb);
    }

    /**
     * Generate the Thumbnail image for particular file.
     */
    protected function create_thumb(string $hash, string $type): bool
    {
        return Media::create_thumbnail_ffmpeg($hash);
    }

    protected function supported_ext(string $ext): bool
    {
        return in_array(strtolower($ext), self::SUPPORTED_EXT);
    }

    protected function create_image_from_data(string $filename, array $metadata): Image
    {
        $image = new Image();

        $size = Media::video_size($filename);
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
        return (
            file_exists($tmpname) &&
            in_array(getMimeType($tmpname), self::SUPPORTED_MIME)
        );
    }
}
