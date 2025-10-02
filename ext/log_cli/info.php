<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogCliInfo extends ExtensionInfo
{
    public const KEY = "log_cli";

    public string $name = "Logging (CLI)";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Print output when run as a CLI tool";
    public bool $core = true;
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
}
