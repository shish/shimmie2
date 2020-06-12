<?php declare(strict_types=1);

class TagListInfo extends ExtensionInfo
{
    public const KEY = "tag_list";

    public $key = self::KEY;
    public $name = "Tag List";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Show the tags in various ways";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
