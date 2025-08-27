<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageBanInfo extends ExtensionInfo
{
    public const KEY = "image_hash_ban";

    public string $name = "Post Hash Ban";
    public array $authors = ["ATravelingGeek" => "mailto:atg@atravelinggeek.com"];
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Ban posts based on their hash";
}
