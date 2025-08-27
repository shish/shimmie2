<?php

declare(strict_types=1);

namespace Shimmie2;

final class HolidayInfo extends ExtensionInfo
{
    public const KEY = "holiday";

    public string $name = "Holiday Theme";
    public array $authors = ["DakuTree" => "mailto:thedakutree@codeanimu.net"];
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Use an additional stylesheet on certain holidays";
}
