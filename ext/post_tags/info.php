<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTagsInfo extends ExtensionInfo
{
    public const KEY = "post_tags";

    public string $name = "Tag Editor";
    public bool $core = true;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow images to have tags assigned to them";
}
