<?php

declare(strict_types=1);

namespace Shimmie2;

class VideoFileHandlerConfig extends ConfigGroup
{
    public const PLAYBACK_AUTOPLAY = "video_playback_autoplay";
    public const PLAYBACK_LOOP = "video_playback_loop";
    public const PLAYBACK_MUTE = "video_playback_mute";
    public const ENABLED_FORMATS = "video_enabled_formats";
}
