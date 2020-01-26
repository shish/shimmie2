<?php declare(strict_types=1);

class StatsDInterfaceInfo extends ExtensionInfo
{
    public const KEY = "statsd";

    public $key = self::KEY;
    public $name = "StatsD Interface";
    public $url = self::SHIMMIE_URL;
    public $authors = self::SHISH_AUTHOR;
    public $license = self::LICENSE_GPLV2;
    public $visibility = self::VISIBLE_ADMIN;
    public $description = "Sends Shimmie stats to a StatsD server";
    public $documentation = "define('STATSD_HOST', 'my.server.com:8125'); in shimmie.conf.php to set the host";
}
