<?php declare(strict_types=1);

class ImageIOInfo extends ExtensionInfo
{
    public const KEY = "image";

    public $key = self::KEY;
    public $name = "Image Manager";
    public $url = self::SHIMMIE_URL;
    public $authors = [self::SHISH_NAME=> self::SHISH_EMAIL, "jgen"=>"jgen.tech@gmail.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Handle the image database";
    public $visibility = self::VISIBLE_HIDDEN;
    public $core = true;
}
