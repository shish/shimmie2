<?php declare(strict_types=1);

class VideoFileHandler extends DataHandlerExtension
{
    protected $SUPPORTED_MIME = [
        'video/webm',
        'video/mp4',
        'video/ogg',
        'video/flv',
        'video/x-flv'
    ];
    protected $SUPPORTED_EXT = ["flv", "mp4", "m4v", "ogv", "webm"];

    public function onInitExt(InitExtEvent $event)
    {
        global $config;

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
                        $event->image->audio = $audio;
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

    protected function create_thumb(string $hash, string $type): bool
    {
        return Media::create_thumbnail_ffmpeg($hash);
    }

    protected function check_contents(string $tmpname): bool
    {
        return in_array(getMimeType($tmpname), $this->SUPPORTED_MIME);
    }
}
