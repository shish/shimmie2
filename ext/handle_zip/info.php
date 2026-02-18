<?php

declare(strict_types=1);

namespace Shimmie2;

final class ZipFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_zip";

    public string $name = "ZIP Archives";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Upload multiple files in one go";
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
}
