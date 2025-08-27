<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagToolsInfo extends ExtensionInfo
{
    public const KEY = "tag_tools";

    public string $name = "Tag Tools";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Recount / Rename / Etc";
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
}
