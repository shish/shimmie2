<?php

declare(strict_types=1);

namespace Shimmie2;

class LogDatabaseInfo extends ExtensionInfo
{
    public const KEY = "log_db";

    public string $key = self::KEY;
    public string $name = "Logging (Database)";
    public string $url = self::SHIMMIE_URL;
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Keep a record of SCore events (in the database).";
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
}
