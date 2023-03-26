<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class MediaConfig
{
    public const FFMPEG_PATH = "media_ffmpeg_path";
    public const FFPROBE_PATH = "media_ffprobe_path";
    public const CONVERT_PATH = "media_convert_path";
    public const VERSION = "ext_media_version";
    public const MEM_LIMIT = 'media_mem_limit';
}
