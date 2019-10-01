<?php

/*
 * Name: Image Manager
 * Author: Shish <webmaster@shishnet.org>
 * Modified by: jgen <jgen.tech@gmail.com>
 * Link: http://code.shishnet.org/shimmie2/
 * Description: Handle the image database
 * Visibility: admin
 */

class ImageIOInfo extends ExtensionInfo
{
    public const KEY = "image";

    public $key = self::KEY;
    public $name = "Image Manager";
    public $url = self::SHIMMIE_URL;
    public $authors = [self::SHISH_NAME=> self::SHISH_EMAIL, "jgen"=>"jgen.tech@gmail.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Handle the image database";
    public $visibility = self::VISIBLE_ADMIN;
    public $core = true;
}
