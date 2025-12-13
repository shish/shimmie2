<?php

declare(strict_types=1);

namespace Shimmie2;

final class TraceOTLPInfo extends ExtensionInfo
{
    public const KEY = "trace_otlp";

    public string $name = "Tracing (OTLP)";
    public array $authors = self::SHISH_AUTHOR;
    public string $description = "Sends slow page traces to an OTLP server";
    public ExtensionCategory $category = ExtensionCategory::OBSERVABILITY;
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public array $dependencies = [OTLPCommonInfo::KEY];
}
