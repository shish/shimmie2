<?php

declare(strict_types=1);

namespace Shimmie2;

final class VideoFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_video";

    public string $name = "Video Files";
    public array $authors = [
        "velocity37" => "mailto:velocity37@gmail.com",
        self::SHISH_NAME => self::SHISH_EMAIL,
        "jgen" => "mailto:jeffgenovy@gmail.com",
        "im-mi" => "mailto:im.mi.mail.mi@gmail.com",
    ];
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Handle MP4 and WEBM video files";
}
