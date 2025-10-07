<?php

declare(strict_types=1);

namespace Shimmie2;

final class TraceOTLPConfig extends ConfigGroup
{
    public const KEY = "trace_otlp";
    public ?string $title = "Tracing (OTLP)";

    #[ConfigMeta("Trace Threshold (ms)", ConfigType::INT, default: 1000)]
    public const TRACE_THRESHOLD = 'trace_otlp_threshold';
}
