<?php declare(strict_types=1);

class LiveFeedInfo extends ExtensionInfo
{
    public const KEY = "livefeed";

    public $key = self::KEY;
    public $name = "Live Feed";
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_ADMIN;
    public $description = "Logs user-safe (no IPs) data to a UDP socket, eg IRCCat";
}
