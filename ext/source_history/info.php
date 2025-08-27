<?php

declare(strict_types=1);

namespace Shimmie2;

final class SourceHistoryInfo extends ExtensionInfo
{
    public const KEY = "source_history";

    public string $name = "Source History";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Keep a record of source changes, and allows you to revert changes";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
