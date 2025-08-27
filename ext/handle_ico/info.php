<?php

declare(strict_types=1);

namespace Shimmie2;

final class IcoFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_ico";

    public string $name = "Icon Files";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FORMAT_SUPPORT;
    public string $description = "Handle windows ICO, ANI, CUR, etc";
}
