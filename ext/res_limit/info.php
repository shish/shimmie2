<?php

declare(strict_types=1);

namespace Shimmie2;

final class ResolutionLimitInfo extends ExtensionInfo
{
    public const KEY = "res_limit";

    public string $name = "Resolution Limiter";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Allows the admin to set min / max image dimensions";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
