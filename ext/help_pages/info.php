<?php declare(strict_types=1);

class HelpPagesInfo extends ExtensionInfo
{
    public const KEY = "help_pages";

    public string $key = self::KEY;
    public string $name = "Help Pages";
    public array $authors = ["Matthew Barbour"=>"matthew@darkholme.net"];
    public string $license = self::LICENSE_WTFPL;
    public string $description = "Provides documentation screens";
    public string $visibility = self::VISIBLE_HIDDEN;
    public bool $core = true;
}
