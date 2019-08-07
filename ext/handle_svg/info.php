<?php

/*
 * Name: Handle SVG
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle static SVG files.
 */

class SVGFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_svg";

    public $key = self::KEY;
    public $name = "Handle SVG";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Handle static SVG files.";
}
