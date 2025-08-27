<?php

declare(strict_types=1);

namespace Shimmie2;

final class RSSCommentsInfo extends ExtensionInfo
{
    public const KEY = "rss_comments";

    public string $name = "RSS for Comments";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Self explanatory";
}
