<?php

declare(strict_types=1);

namespace Shimmie2;

final class TraceChromeConfig extends ConfigGroup
{
    public const KEY = "trace_chrome";
    public ?string $title = "Tracing (Chrome)";

    #[ConfigMeta("Trace File", ConfigType::STRING, default: null)]
    public const TRACE_FILE = 'trace_chrome_file';

    #[ConfigMeta("Trace Threshold (ms)", ConfigType::INT, default: 2000)]
    public const TRACE_THRESHOLD = 'trace_chrome_threshold';
}
