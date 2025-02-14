<?php

declare(strict_types=1);

namespace Shimmie2;

class VideoFileHandlerConfig extends ConfigGroup
{
    #[ConfigMeta("Autoplay", ConfigType::BOOL)]
    public const PLAYBACK_AUTOPLAY = "video_playback_autoplay";

    #[ConfigMeta("Loop", ConfigType::BOOL)]
    public const PLAYBACK_LOOP = "video_playback_loop";

    #[ConfigMeta("Mute", ConfigType::BOOL)]
    public const PLAYBACK_MUTE = "video_playback_mute";

    #[ConfigMeta("Enabled formats", ConfigType::ARRAY, options: "Shimmie2\VideoFileHandlerConfig::get_enabled_mime_options")]
    public const ENABLED_FORMATS = "video_enabled_formats";

    /**
     * @return array<string, string>
     */
    public static function get_enabled_mime_options(): array
    {
        $output = [];
        foreach (VideoFileHandler::SUPPORTED_MIME as $mime) {
            $output[MimeMap::get_name_for_mime($mime)] = $mime;
        }
        return $output;
    }

}
