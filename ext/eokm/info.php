<?php

declare(strict_types=1);

namespace Shimmie2;

final class EokmInfo extends ExtensionInfo
{
    public const KEY = "eokm";

    public string $name = "EOKM Filter";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Check uploads against the EOKM blocklist";
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
}
