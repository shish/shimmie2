<?php

/*
 * Name: Resolution Limiter
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Allows the admin to set min / max image dimensions
 */
class ResolutionLimitInfo extends ExtensionInfo
{
    public const KEY = "res_limit";

    public $key = self::KEY;
    public $name = "Resolution Limiter";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Allows the admin to set min / max image dimensions";
}
