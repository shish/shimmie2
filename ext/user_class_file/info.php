<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserClassFileInfo extends ExtensionInfo
{
    public const KEY = "user_class_file";

    public string $name = "Custom User Classes (File)";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::ADMIN;
    public string $description = "Load custom user classes from user-classes.conf.php";
}
