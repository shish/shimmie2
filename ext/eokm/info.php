<?php declare(strict_types=1);

class EokmInfo extends ExtensionInfo
{
    public const KEY = "eokm";

    public $key = self::KEY;
    public $name = "EOKM Filter";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $description = "Check uploads against the EOKM blocklist";
}
