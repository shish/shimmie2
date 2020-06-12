<?php declare(strict_types=1);

class HelpPagesInfo extends ExtensionInfo
{
    public const KEY = "help_pages";

    public $key = self::KEY;
    public $name = "Help Pages";
    public $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public $license = self::LICENSE_WTFPL;
    public $description = "Provides documentation screens";
    public $visibility = self::VISIBLE_HIDDEN;
    public $core = true;
}
