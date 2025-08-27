<?php

declare(strict_types=1);

namespace Shimmie2;

final class WordFilterInfo extends ExtensionInfo
{
    public const KEY = "word_filter";

    public string $name = "Word Filter";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Simple search and replace";
}
