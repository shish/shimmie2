<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogNetInfo extends ExtensionInfo
{
    public const KEY = "log_net";

    public string $name = "Logging (Network)";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Send log events to a network port";
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
}
