<?php

declare(strict_types=1);

namespace Shimmie2;

class PermManagerInfo extends ExtensionInfo
{
    public const KEY = "perm_manager";

    public string $key = self::KEY;
    public string $name = "Permission Manager";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Allows the admin to modify user class permissions.";
    public bool $core = true;
}
