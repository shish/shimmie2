<?php

declare(strict_types=1);

namespace Shimmie2;

final class VideoFileHandlerConfig extends ConfigGroup
{
    public const KEY = "handle_video";

    #[ConfigMeta("FFmpeg path", ConfigType::STRING, default: "ffmpeg")]
    public const FFMPEG_PATH = "media_ffmpeg_path";

    #[ConfigMeta("FFprobe path", ConfigType::STRING, default: "ffprobe")]
    public const FFPROBE_PATH = "media_ffprobe_path";

    #[ConfigMeta("Autoplay", ConfigType::BOOL, default: true)]
    public const PLAYBACK_AUTOPLAY = "video_playback_autoplay";

    #[ConfigMeta("Loop", ConfigType::BOOL, default: true)]
    public const PLAYBACK_LOOP = "video_playback_loop";

    #[ConfigMeta("Mute", ConfigType::BOOL, default: false)]
    public const PLAYBACK_MUTE = "video_playback_mute";
}
