<?php

declare(strict_types=1);

namespace Shimmie2;

final class OTLPCommonInfo extends ExtensionInfo
{
    public const KEY = "otlp_common";

    public string $name = "OTLP Settings";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Common settings for all OTLP loggers";
    public ExtensionVisibility $visibility = ExtensionVisibility::HIDDEN;
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
}
