<?php

declare(strict_types=1);

namespace Shimmie2;

class UserConfigInfo extends ExtensionInfo
{
    public const KEY = "user_config";

    public string $key = self::KEY;
    public string $name = "User-specific settings";
    public array $authors = ["Matthew Barbour" => "matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides system-wide support for user-specific settings";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public bool $core = true;
}
