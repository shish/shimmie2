<?php

declare(strict_types=1);

namespace Shimmie2;

final class VideoFileHandlerConfig extends ConfigGroup
{
    public const KEY = "handle_video";

    #[ConfigMeta("Autoplay", ConfigType::BOOL, default: true)]
    public const PLAYBACK_AUTOPLAY = "video_playback_autoplay";

    #[ConfigMeta("Loop", ConfigType::BOOL, default: true)]
    public const PLAYBACK_LOOP = "video_playback_loop";

    #[ConfigMeta("Mute", ConfigType::BOOL, default: false)]
    public const PLAYBACK_MUTE = "video_playback_mute";

    #[ConfigMeta(
        "Enabled formats",
        ConfigType::ARRAY,
        default: [MimeType::FLASH_VIDEO, MimeType::MP4_VIDEO, MimeType::OGG_VIDEO, MimeType::WEBM],
        options: "Shimmie2\VideoFileHandlerConfig::get_enabled_mime_options"
    )]
    public const ENABLED_FORMATS = "video_enabled_formats";

    /**
     * @return array<string, string>
     */
    public static function get_enabled_mime_options(): array
    {
        $output = [];
        foreach (VideoFileHandler::SUPPORTED_MIME as $mime) {
            $output[MimeMap::get_name_for_mime(new MimeType($mime))] = $mime;
        }
        return $output;
    }

}
