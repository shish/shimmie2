<?php declare(strict_types=1);

class UserConfigInfo extends ExtensionInfo
{
    public const KEY = "user_config";

    public $key = self::KEY;
    public $name = "User-specific settings";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides system-wide support for user-specific settings";
    public $visibility = self::VISIBLE_HIDDEN;
    public $core = true;
}
