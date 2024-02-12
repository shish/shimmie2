<?php

declare(strict_types=1);

namespace Shimmie2;

class PostOwnerInfo extends ExtensionInfo
{
    public const KEY = "post_owner";

    public string $key = self::KEY;
    public string $name = "Owner Editor";
    public bool $core = true;
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::METADATA;
    public string $description = "Allow images to have owners assigned to them";
}
