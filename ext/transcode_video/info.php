<?php

declare(strict_types=1);

namespace Shimmie2;

class TranscodeVideoInfo extends ExtensionInfo
{
    public const KEY = "transcode_video";

    public string $key = self::KEY;
    public string $name = "Transcode Video";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows admins to automatically and manually transcode videos.";
    public ?string $documentation = "Requires ffmpeg";
}
