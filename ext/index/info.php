<?php

declare(strict_types=1);

namespace Shimmie2;

final class IndexInfo extends ExtensionInfo
{
    public const KEY = "index";

    public string $key = self::KEY;
    public string $name = "Post List";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::FEATURE;
    public string $description = "Show a list of uploaded posts";
    public bool $core = true;
}
