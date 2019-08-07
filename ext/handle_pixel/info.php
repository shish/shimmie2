<?php

/**
 * Name: Handle Pixel
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle JPEG, PNG, GIF, WEBP, etc files
 */

class PixelFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_pixel";

    public $key = self::KEY;
    public $name = "Handle Pixel";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $description = "Handle JPEG, PNG, GIF, WEBP, etc files";
    public $core = true;
}
