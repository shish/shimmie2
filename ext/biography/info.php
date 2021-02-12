<?php declare(strict_types=1);

class BiographyInfo extends ExtensionInfo
{
    public const KEY = "biography";

    public $key = self::KEY;
    public $name = "User Bios";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Allow users to write a bit about themselves";
}
