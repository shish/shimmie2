<?php

declare(strict_types=1);

namespace Shimmie2;

class SourceHistoryInfo extends ExtensionInfo
{
    public const KEY = "source_history";

    public string $key = self::KEY;
    public string $name = "Source History";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Keep a record of source changes, and allows you to revert changes.";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
