<?php declare(strict_types=1);

class SystemInfo extends ExtensionInfo
{
    public const KEY = "system";

    public $key = self::KEY;
    public $name = "System";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides system screen";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
