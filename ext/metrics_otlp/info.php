<?php

declare(strict_types=1);

namespace Shimmie2;

final class MetricsOTLPInfo extends ExtensionInfo
{
    public const KEY = "metrics_otlp";

    public string $name = "Metrics (OTLP)";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public string $description = "Sends Shimmie stats to an OTLP server";
}
