<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogLogstashConfig extends ConfigGroup
{
    public const KEY = "log_logstash";

    #[ConfigMeta("host:port", ConfigType::STRING, default: "127.0.0.1:1234")]
    public const HOST = "log_logstash_host";
}
