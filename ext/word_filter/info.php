<?php declare(strict_types=1);

class WordFilterInfo extends ExtensionInfo
{
    public const KEY = "word_filter";

    public $key = self::KEY;
    public $name = "Word Filter";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Simple search and replace";
}
