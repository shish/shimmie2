<?php

declare(strict_types=1);

namespace Shimmie2;

class CBZFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_cbz";

    public string $key = self::KEY;
    public string $name = "Handle CBZ";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Handle CBZ Comic Archives.";
}
