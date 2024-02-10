<?php

declare(strict_types=1);

namespace Shimmie2;

class EokmInfo extends ExtensionInfo
{
    public const KEY = "eokm";

    public string $key = self::KEY;
    public string $name = "EOKM Filter";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Check uploads against the EOKM blocklist";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
