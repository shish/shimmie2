<?php

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

    public function onMediaCheckProperties(MediaCheckPropertiesEvent $event)
    {
        if (in_array($event->ext, self::SUPPORTED_EXT)) {
            $event->video = true;
            $event->image = false;
            try {
                $data = Media::get_ffprobe_data($event->file_name);

                if (is_array($data)) {
                    if (array_key_exists("streams", $data)) {
                        $video = false;
                        $audio = true;
                        $streams = $data["streams"];
                        if (is_array($streams)) {
                            foreach ($streams as $stream) {
                                if (is_array($stream)) {
                                    if (array_key_exists("codec_type", $stream)) {
                                        $type = $stream["codec_type"];
                                        switch ($type) {
                                            case "audio":
                                                $audio = true;
                                                break;
                                            case "video":
                                                $video = true;
                                                break;
                                        }
                                    }
                                    if (array_key_exists("width", $stream) && !empty($stream["width"])
                                        && is_numeric($stream["width"]) && intval($stream["width"]) > ($event->width) ?? 0) {
                                        $event->width = intval($stream["width"]);
                                    }
                                    if (array_key_exists("height", $stream) && !empty($stream["height"])
                                        && is_numeric($stream["height"]) && intval($stream["height"]) > ($event->height) ?? 0) {
                                        $event->height = intval($stream["height"]);
                                    }
                                }
                            }
                            $event->video = $video;
                            $event->audio = $audio;
                        }
                    }
                    if (array_key_exists("format", $data)&& is_array($data["format"])) {
                        $format = $data["format"];
                        if (array_key_exists("duration", $format) && is_numeric($format["duration"])) {
                            $event->length = floor(floatval($format["duration"]) * 1000);
                        }
                    }
                }
            } catch (MediaException $e) {
                // a post with no metadata is better than no post
            }
        }
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
