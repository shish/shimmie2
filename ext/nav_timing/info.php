<?php

declare(strict_types=1);

namespace Shimmie2;

final class NavTimingInfo extends ExtensionInfo
{
    public const KEY = "nav_timing";

    public string $name = "Nav Timing";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Log navigation timing data";
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
}
