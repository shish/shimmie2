<?php

declare(strict_types=1);

namespace Shimmie2;

class IcoFileHandlerInfo extends ExtensionInfo
{
    public const KEY = "handle_ico";

    public string $key = self::KEY;
    public string $name = "Handle ICO";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::FILE_HANDLING;
    public string $description = "Handle windows icons";
}
