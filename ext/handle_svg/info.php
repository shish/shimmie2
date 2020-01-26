<?php declare(strict_types=1);

class SVGFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_svg";

    public $key = self::KEY;
    public $name = "Handle SVG";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Handle static SVG files.";
}
