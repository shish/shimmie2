<?php

declare(strict_types=1);

namespace Shimmie2;

final class CBZFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_cbz";

    public string $name = "CBZ Comics";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Handle CBZ Comic Archives";
}
