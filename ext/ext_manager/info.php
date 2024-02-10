<?php

declare(strict_types=1);

namespace Shimmie2;

class ExtManagerInfo extends ExtensionInfo
{
    public const KEY = "ext_manager";

    public string $key = self::KEY;
    public string $name = "Extension Manager";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public string $license = self::LICENSE_GPLV2;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "A thing for point & click extension management";
    public ?string $documentation = "Allows the admin to view a list of all extensions and enable or disable them; also allows users to view the list of activated extensions and read their documentation";
    public bool $core = true;
}
