<?php declare(strict_types=1);

class ViewImageInfo extends ExtensionInfo
{
    public const KEY = "view";

    public $key = self::KEY;
    public $name = "Post Viewer";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Allows users to see uploaded posts";
    public $core = true;
    public $visibility = self::VISIBLE_HIDDEN;
}
