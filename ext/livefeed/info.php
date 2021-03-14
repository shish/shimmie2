<?php declare(strict_types=1);

class LiveFeedInfo extends ExtensionInfo
{
    public const KEY = "livefeed";

    public string $key = self::KEY;
    public string $name = "Live Feed";
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $visibility = self::VISIBLE_ADMIN;
    public string $description = "Logs user-safe (no IPs) data to a UDP socket, eg IRCCat";
}
