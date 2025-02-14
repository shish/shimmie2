<?php

declare(strict_types=1);

namespace Shimmie2;

class MediaConfig extends ConfigGroup
{
    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_media_version";

    #[ConfigMeta("FFmpeg path", ConfigType::STRING)]
    public const FFMPEG_PATH = "media_ffmpeg_path";

    #[ConfigMeta("FFprobe path", ConfigType::STRING)]
    public const FFPROBE_PATH = "media_ffprobe_path";

    #[ConfigMeta("Convert path", ConfigType::STRING)]
    public const CONVERT_PATH = "media_convert_path";

    #[ConfigMeta("Memory limit", ConfigType::INT, ui_type: "shorthand_int")]
    public const MEM_LIMIT = 'media_mem_limit';
}
