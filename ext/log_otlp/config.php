<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogOTLPConfig extends ConfigGroup
{
    public const KEY = "log_otlp";
    public ?string $title = "Log OTLP";

    #[ConfigMeta("host:port", ConfigType::STRING, default: "http://localhost:4318/v1/logs")]
    public const HOST = "log_otlp";
}
