<?php

declare(strict_types=1);

namespace Shimmie2;

class MediaInfo extends ExtensionInfo
{
    public const KEY = "media";

    public string $key = self::KEY;
    public string $name = "Media";
    public string $url = self::SHIMMIE_URL;
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides common functions and settings used for media operations.";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
