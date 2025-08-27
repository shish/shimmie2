<?php

declare(strict_types=1);

namespace Shimmie2;

final class IndexInfo extends ExtensionInfo
{
    public const KEY = "index";

    public string $name = "Post List";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Show a list of uploaded posts";
    public bool $core = true;
}
