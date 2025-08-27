<?php

declare(strict_types=1);

namespace Shimmie2;

final class StatsDInterfaceInfo extends ExtensionInfo
{
    public const KEY = "statsd";

    public string $name = "StatsD Interface";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Sends Shimmie stats to a StatsD server";
}
