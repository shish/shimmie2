<?php

declare(strict_types=1);

namespace Shimmie2;

class ReportImageInfo extends ExtensionInfo
{
    public const KEY = "report_image";

    public string $key = self::KEY;
    public string $name = "Report Posts";
    public string $url = "http://atravelinggeek.com/";
    public array $authors = ["ATravelingGeek" => "atg@atravelinggeek.com"];
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Report posts as dupes/illegal/etc";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
