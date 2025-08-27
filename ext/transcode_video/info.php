<?php

declare(strict_types=1);

namespace Shimmie2;

final class TranscodeVideoInfo extends ExtensionInfo
{
    public const KEY = "transcode_video";

    public string $name = "Transcode Video";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows admins to manually transcode videos";
    public array $dependencies = [VideoFileHandlerInfo::KEY, ReplaceFileInfo::KEY];
}
