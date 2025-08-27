<?php

declare(strict_types=1);

namespace Shimmie2;

final class ReplaceFileInfo extends ExtensionInfo
{
    public const KEY = "replace_file";

    public string $name = "Replace File";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows people to replace files for existing posts";
}
