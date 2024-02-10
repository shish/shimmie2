<?php

declare(strict_types=1);

namespace Shimmie2;

class StatsDInterfaceInfo extends ExtensionInfo
{
    public const KEY = "statsd";

    public string $key = self::KEY;
    public string $name = "StatsD Interface";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Sends Shimmie stats to a StatsD server";
    public ?string $documentation = "define('STATSD_HOST', 'my.server.com:8125'); in shimmie.conf.php to set the host";
}
