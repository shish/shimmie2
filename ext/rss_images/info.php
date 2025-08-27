<?php

declare(strict_types=1);

namespace Shimmie2;

final class RSSImagesInfo extends ExtensionInfo
{
    public const KEY = "rss_images";

    public string $name = "RSS for Posts";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Self explanatory";
}
