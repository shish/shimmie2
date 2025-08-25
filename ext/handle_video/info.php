<?php

declare(strict_types=1);

namespace Shimmie2;

final class VideoFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_video";

    public string $key = self::KEY;
    public string $name = "Handle Video Files";
    public array $authors = ["velocity37" => "velocity37@gmail.com",self::SHISH_NAME => self::SHISH_EMAIL, "jgen" => "jeffgenovy@gmail.com", "im-mi" => "im.mi.mail.mi@gmail.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Handle MP4 and WEBM video files";
}
