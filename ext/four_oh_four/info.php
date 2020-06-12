<?php declare(strict_types=1);

class FourOhFourInfo extends ExtensionInfo
{
    public const KEY = "four_oh_four";

    public $key = self::KEY;
    public $name = "404 Detector";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_HIDDEN;
    public $description = "If no other extension puts anything onto the page, show 404";
    public $core = true;
}
