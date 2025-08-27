<?php

declare(strict_types=1);

namespace Shimmie2;

final class UpgradeInfo extends ExtensionInfo
{
    public const KEY = "upgrade";

    public string $name = "Database Upgrader";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Keeps things happy behind the scenes";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public bool $core = true;
}
