<?php

declare(strict_types=1);

namespace Shimmie2;

class PostLockInfo extends ExtensionInfo
{
    public const KEY = "post_lock";

    public string $key = self::KEY;
    public string $name = "Lock Editor";
    public bool $core = true;
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow images to have metadata locked";
}
