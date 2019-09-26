<?php

/*
 * Name: Image Hash Ban
 * Author: ATravelingGeek <atg@atravelinggeek.com>
 * Link: http://atravelinggeek.com/
 * License: GPLv2
 * Description: Ban images based on their hash
 * Based on the ResolutionLimit and IPban extensions by Shish
 * Version 0.1, October 21, 2007
 */

class ImageBanInfo extends ExtensionInfo
{
    public const KEY = "image_hash_ban";

    public $key = self::KEY;
    public $name = "Image Hash Ban";
    public $url = "http://atravelinggeek.com/";
    public $authors = ["ATravelingGeek"=>"atg@atravelinggeek.com"];
    public $license = self::LICENSE_GPLV2;
    public $description = "Ban images based on their hash";
    public $version = "0.1, October 21, 2007";
    public $documentation =
"Based on the ResolutionLimit and IPban extensions by Shish";
}
