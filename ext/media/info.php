<?php

declare(strict_types=1);

namespace Shimmie2;

final class MediaInfo extends ExtensionInfo
{
    public const KEY = "media";

    public string $name = "Media";
    public array $authors = ["Matthew Barbour" => "mailto:matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides common functions and settings used for media operations";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
