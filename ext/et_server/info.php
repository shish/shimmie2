<?php declare(strict_types=1);

class ETServerInfo extends ExtensionInfo
{
    public const KEY = "et_server";

    public $key = self::KEY;
    public $name = "System Info Registry";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Keep track of shimmie registrations";
    public $documentation = "For internal use";
    public $visibility = self::VISIBLE_HIDDEN;
}
