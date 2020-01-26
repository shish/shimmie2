<?php declare(strict_types=1);

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
