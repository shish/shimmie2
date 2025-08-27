<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogDatabaseInfo extends ExtensionInfo
{
    public const KEY = "log_db";

    public string $name = "Logging (Database)";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Keep a record of site events (in the database)";
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
}
