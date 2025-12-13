<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogOTLPInfo extends ExtensionInfo
{
    public const KEY = "log_otlp";

    public string $name = "Logging (OTLP)";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Sends logs to an OTLP server";
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public array $dependencies = [OTLPCommonInfo::KEY];
}
