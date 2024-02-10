<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageBanInfo extends ExtensionInfo
{
    public const KEY = "image_hash_ban";

    public string $key = self::KEY;
    public string $name = "Post Hash Ban";
    public string $url = "http://atravelinggeek.com/";
    public array $authors = ["ATravelingGeek" => "atg@atravelinggeek.com"];
    public string $license = self::LICENSE_GPLV2;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Ban images based on their hash";
    public ?string $documentation =
"Based on the ResolutionLimit and IPban extensions by Shish";
}
