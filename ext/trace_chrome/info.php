<?php

declare(strict_types=1);

namespace Shimmie2;

final class TraceChromeInfo extends ExtensionInfo
{
    public const KEY = "trace_chrome";

    public string $name = "Chrome EventTrace Logger";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Log slow request traces to a JSON file";
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
}
