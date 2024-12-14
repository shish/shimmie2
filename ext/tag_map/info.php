<?php

declare(strict_types=1);

namespace Shimmie2;

class TagMapInfo extends ExtensionInfo
{
    public const KEY = "tag_map";

    public string $key = self::KEY;
    public string $name = "Tag Map";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Show the tags in various ways";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
}
