<?php declare(strict_types=1);

class UserConfigInfo extends ExtensionInfo
{
    public const KEY = "user_config";

    public string $key = self::KEY;
    public string $name = "User-specific settings";
    public array $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides system-wide support for user-specific settings";
    public string $visibility = self::VISIBLE_HIDDEN;
    public bool $core = true;
}
