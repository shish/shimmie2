<?php

declare(strict_types=1);

namespace Shimmie2;

class TagToolsInfo extends ExtensionInfo
{
    public const KEY = "tag_tools";

    public string $key = self::KEY;
    public string $name = "Tag Tools";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Recount / Rename / Etc";
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
}
