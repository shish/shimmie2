<?php declare(strict_types=1);

abstract class VideoFileHandlerConfig
{
    public const PLAYBACK_AUTOPLAY = "video_playback_autoplay";
    public const PLAYBACK_LOOP = "video_playback_loop";
    public const PLAYBACK_MUTE = "video_playback_mute";
    public const ENABLED_FORMATS = "video_enabled_formats";
}

class VideoFileHandler extends DataHandlerExtension
{
    public const SUPPORTED_MIME = [
        MimeType::ASF,
        MimeType::AVI,
        MimeType::FLASH_VIDEO,
        MimeType::MKV,
        MimeType::MP4_VIDEO,
        MimeType::OGG_VIDEO,
        MimeType::QUICKTIME,
        MimeType::WEBM,
    ];
    protected $SUPPORTED_MIME = self::SUPPORTED_MIME;

    public function onInitExt(InitExtEvent $event)
    {
        global $config;

        $config->set_default_bool(VideoFileHandlerConfig::PLAYBACK_AUTOPLAY, true);
        $config->set_default_bool(VideoFileHandlerConfig::PLAYBACK_LOOP, true);
        $config->set_default_bool(VideoFileHandlerConfig::PLAYBACK_MUTE, false);
        $config->set_default_array(
            VideoFileHandlerConfig::ENABLED_FORMATS,
            [MimeType::FLASH_VIDEO, MimeType::MP4_VIDEO, MimeType::OGG_VIDEO, MimeType::WEBM]
        );
    }

    private function get_options(): array
    {
        $output = [];
        foreach ($this->SUPPORTED_MIME as $mime) {
            $output[MimeMap::get_name_for_mime($mime)] = $mime;
        }
        return $output;
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = new SetupBlock("Video Options");
        $sb->start_table();
        $sb->add_bool_option(VideoFileHandlerConfig::PLAYBACK_AUTOPLAY, "Autoplay", true);
        $sb->add_bool_option(VideoFileHandlerConfig::PLAYBACK_LOOP, "Loop", true);
        $sb->add_bool_option(VideoFileHandlerConfig::PLAYBACK_MUTE, "Mute", true);
        $sb->add_multichoice_option(VideoFileHandlerConfig::ENABLED_FORMATS, $this->get_options(), "Enabled Formats", true);
        $sb->end_table();
        $event->panel->add_block($sb);
    }

    protected function media_check_properties(MediaCheckPropertiesEvent $event): void
    {
        $event->image->video = true;
        $event->image->image = false;
        try {
            $data = Media::get_ffprobe_data($event->file_name);

            if (is_array($data)) {
                if (array_key_exists("streams", $data)) {
                    $video = false;
                    $audio = true;
                    $video_codec = null;
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
                                            $video_codec = $stream["codec_name"];
                                            break;
                                    }
                                }
                                if (array_key_exists("width", $stream) && !empty($stream["width"])
                                    && is_numeric($stream["width"]) && intval($stream["width"]) > ($event->image->width) ?? 0) {
                                    $event->image->width = intval($stream["width"]);
                                }
                                if (array_key_exists("height", $stream) && !empty($stream["height"])
                                    && is_numeric($stream["height"]) && intval($stream["height"]) > ($event->image->height) ?? 0) {
                                    $event->image->height = intval($stream["height"]);
                                }
                            }
                        }
                        $event->image->video = $video;
                        $event->image->video_codec = $video_codec;
                        $event->image->audio = $audio;
                        if($event->image->get_mime()==MimeType::MKV &&
                            VideoContainers::is_video_codec_supported(VideoContainers::WEBM,$event->image->video_codec)) {
                            // WEBMs are MKVs with the VP9 or VP8 codec
                            // For browser-friendliness, we'll just change the mime type
                            $event->image->set_mime(MimeType::WEBM);
                        }
                    }
                }
                if (array_key_exists("format", $data)&& is_array($data["format"])) {
                    $format = $data["format"];
                    if (array_key_exists("duration", $format) && is_numeric($format["duration"])) {
                        $event->image->length = floor(floatval($format["duration"]) * 1000);
                    }
                }
            }
        } catch (MediaException $e) {
            // a post with no metadata is better than no post
        }
    }

    protected function supported_mime(string $mime): bool
    {
        global $config;

        $enabled_formats = $config->get_array(VideoFileHandlerConfig::ENABLED_FORMATS);

        return MimeType::matches_array($mime, $enabled_formats, true);
    }

    protected function create_thumb(string $hash, string $type): bool
    {
        return Media::create_thumbnail_ffmpeg($hash);
    }

    protected function check_contents(string $tmpname): bool
    {
        global $config;

        if (file_exists($tmpname)) {
            $mime = MimeType::get_for_file($tmpname);

            $enabled_formats = $config->get_array(VideoFileHandlerConfig::ENABLED_FORMATS);
            if (MimeType::matches_array($mime, $enabled_formats)) {
                return true;
            }
        }
        return false;
    }
}
