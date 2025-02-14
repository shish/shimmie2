<?php

declare(strict_types=1);

namespace Shimmie2;

class TranscodeVideoConfig extends ConfigGroup
{
    public const KEY = "transcode_video";
    public ?string $title = "Transcode Video";

    #[ConfigMeta("Allow transcoding video", ConfigType::BOOL)]
    public const ENABLED = "transcode_video_enabled";

    #[ConfigMeta("Convert videos using MPEG-4 or WEBM to their native containers", ConfigType::BOOL)]
    public const UPLOAD_TO_NATIVE_CONTAINER = "transcode_video_upload_to_native_container";
}
