<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogConsoleInfo extends ExtensionInfo
{
    public const KEY = "log_console";

    public string $name = "Logging (Console)";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Send log events to the command line console";
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
}
