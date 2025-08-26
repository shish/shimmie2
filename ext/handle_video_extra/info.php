<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtraVideoFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_video_extra";

    public string $key = self::KEY;
    public string $name = "Video Files++";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Convert various video formats to WEBM or MP4 during upload";
}
