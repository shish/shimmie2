<?php declare(strict_types=1);

class ImageBanInfo extends ExtensionInfo
{
    public const KEY = "image_hash_ban";

    public $key = self::KEY;
    public $name = "Post Hash Ban";
    public $url = "http://atravelinggeek.com/";
    public $authors = ["ATravelingGeek"=>"atg@atravelinggeek.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Ban images based on their hash";
    public $version = "0.1, October 21, 2007";
    public $documentation =
"Based on the ResolutionLimit and IPban extensions by Shish";
}
