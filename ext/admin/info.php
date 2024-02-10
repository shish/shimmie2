<?php

declare(strict_types=1);

namespace Shimmie2;

class AdminPageInfo extends ExtensionInfo
{
    public const KEY = "admin";

    public string $key = self::KEY;
    public string $name = "Admin Controls";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public string $description = "Provides a base for various small admin functions";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
}
