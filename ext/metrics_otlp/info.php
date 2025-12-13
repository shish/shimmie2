<?php

declare(strict_types=1);

namespace Shimmie2;

final class MetricsOTLPInfo extends ExtensionInfo
{
    public const KEY = "metrics_otlp";

    public string $name = "Metrics (OTLP)";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Sends stats to an OTLP server";
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public array $dependencies = [OTLPCommonInfo::KEY];
}
