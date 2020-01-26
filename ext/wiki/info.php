<?php declare(strict_types=1);

class WikiInfo extends ExtensionInfo
{
    public const KEY = "wiki";

    public $key = self::KEY;
    public $name = "Simple Wiki";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "A simple wiki, for those who don't want the hugeness of mediawiki";
    public $documentation = "Standard formatting APIs are used (This will be BBCode by default)";
}
