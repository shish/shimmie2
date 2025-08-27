<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_image";

    public string $name = "Image Files";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Handle JPEG, PNG, GIF, and similar files";
    public bool $core = true;
}
