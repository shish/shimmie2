<?php

declare(strict_types=1);

namespace Shimmie2;

final class MediaConfig extends ConfigGroup
{
    public const KEY = "media";

    #[ConfigMeta("FFmpeg path", ConfigType::STRING, default: "ffmpeg")]
    public const FFMPEG_PATH = "media_ffmpeg_path";

    #[ConfigMeta("FFprobe path", ConfigType::STRING, default: "ffprobe")]
    public const FFPROBE_PATH = "media_ffprobe_path";

    #[ConfigMeta("Convert path", ConfigType::STRING, default: "convert")]
    public const CONVERT_PATH = "media_convert_path";

    #[ConfigMeta("Memory limit", ConfigType::INT, input: ConfigInput::BYTES, default: 8 * 1024 * 1024)]
    public const MEM_LIMIT = 'media_mem_limit';
}
