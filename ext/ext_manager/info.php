<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtManagerInfo extends ExtensionInfo
{
    public const KEY = "ext_manager";

    public string $name = "Extension Manager";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "A thing for point & click extension management";
    public bool $core = true;
}
