<?php

declare(strict_types=1);

namespace Shimmie2;

final class DevToolsInfo extends ExtensionInfo
{
    public const KEY = "devtools";

    public string $key = self::KEY;
    public string $name = "DevTools";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Assorted bits to make development easier";
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
}
