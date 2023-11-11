<?php

declare(strict_types=1);

namespace Shimmie2;

class DownloadInfo extends ExtensionInfo
{
    public const KEY = "download";

    public string $key = self::KEY;
    public string $name = "Download";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "System-wide download functions";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
