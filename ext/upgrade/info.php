<?php declare(strict_types=1);

class UpgradeInfo extends ExtensionInfo
{
    public const KEY = "upgrade";

    public string $key = self::KEY;
    public string $name = "Database Upgrader";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Keeps things happy behind the scenes";
    public string $visibility = self::VISIBLE_HIDDEN;
    public bool $core = true;
}
