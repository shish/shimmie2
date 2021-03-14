<?php declare(strict_types=1);

class VarnishPurgerInfo extends ExtensionInfo
{
    public const KEY = "varnish";

    public string $key = self::KEY;
    public string $name = "Varnish Purger";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $visibility = self::VISIBLE_ADMIN;
    public string $description = "Sends PURGE requests when a /post/view is updated";
}
