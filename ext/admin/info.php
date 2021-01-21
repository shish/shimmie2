<?php declare(strict_types=1);

class AdminPageInfo extends ExtensionInfo
{
    public const KEY = "admin";

    public $key = self::KEY;
    public $name = "Admin Controls";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Provides a base for various small admin functions";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
