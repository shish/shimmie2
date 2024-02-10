<?php

declare(strict_types=1);

namespace Shimmie2;

class ResolutionLimitInfo extends ExtensionInfo
{
    public const KEY = "res_limit";

    public string $key = self::KEY;
    public string $name = "Resolution Limiter";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Allows the admin to set min / max image dimensions";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
