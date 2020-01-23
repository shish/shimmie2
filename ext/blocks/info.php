<?php declare(strict_types=1);

class BlocksInfo extends ExtensionInfo
{
    public const KEY = "blocks";

    public $key = self::KEY;
    public $name = "Generic Blocks";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Add HTML to some space (News, Ads, etc)";
}
