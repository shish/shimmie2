<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagListInfo extends ExtensionInfo
{
    public const KEY = "tag_list";

    public string $name = "Tag List";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Show the tags in various ways";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
}
