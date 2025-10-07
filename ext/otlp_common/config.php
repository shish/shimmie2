<?php

declare(strict_types=1);

namespace Shimmie2;

final class OTLPCommonConfig extends ConfigGroup
{
    public const KEY = "otlp_common";
    public ?string $title = "OTLP";

    #[ConfigMeta("Collector", ConfigType::STRING, default: "http://localhost:4318")]
    public const HOST = "otlp_collector";
}
