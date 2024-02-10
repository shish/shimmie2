<?php

declare(strict_types=1);

namespace Shimmie2;

class HolidayInfo extends ExtensionInfo
{
    public const KEY = "holiday";

    public string $key = self::KEY;
    public string $name = "Holiday Theme";
    public string $url = "http://www.codeanimu.net";
    public array $authors = ["DakuTree" => "thedakutree@codeanimu.net"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Use an additional stylesheet on certain holidays";
}
