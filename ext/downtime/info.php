<?php declare(strict_types=1);

class DowntimeInfo extends ExtensionInfo
{
    public const KEY = "downtime";

    public $key = self::KEY;
    public $name = "Downtime";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Show a \"down for maintenance\" page";
    public $documentation =
"Once installed there will be some more options on the config page --
Ticking \"disable non-admin access\" will mean that regular and anonymous
users will be blocked from accessing the site, only able to view the
message specified in the box.";
}
