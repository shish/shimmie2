<?php declare(strict_types=1);

class TombstonesInfo extends ExtensionInfo
{
    public const KEY = "tombstones";

    public $key = self::KEY;
    public $name = "Tombstones";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Provide a marker listing some details about deleted posts";
}
