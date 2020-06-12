<?php declare(strict_types=1);

class UpgradeInfo extends ExtensionInfo
{
    public const KEY = "upgrade";

    public $key = self::KEY;
    public $name = "Database Upgrader";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Keeps things happy behind the scenes";
    public $visibility = self::VISIBLE_HIDDEN;
    public $core = true;
}
