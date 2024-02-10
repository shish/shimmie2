<?php

declare(strict_types=1);

namespace Shimmie2;

class DowntimeInfo extends ExtensionInfo
{
    public const KEY = "downtime";

    public string $key = self::KEY;
    public string $name = "Downtime";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Show a \"down for maintenance\" page";
    public ?string $documentation =
"Once installed there will be some more options on the config page --
Ticking \"disable non-admin access\" will mean that regular and anonymous
users will be blocked from accessing the site, only able to view the
message specified in the box.";
}
