<?php

declare(strict_types=1);

namespace Shimmie2;

class ReplaceFileInfo extends ExtensionInfo
{
    public const KEY = "replace_file";

    public string $key = self::KEY;
    public string $name = "Replace File";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Allows people to replace files for existing posts";

    // Core because several other extensions depend on it, this could be
    // non-core if we had a way to specify dependencies dynamically
    public bool $core = true;
}
