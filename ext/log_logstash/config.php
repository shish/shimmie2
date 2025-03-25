<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogLogstashConfig extends ConfigGroup
{
    public const KEY = "log_logstash";

    #[ConfigMeta("host:port", ConfigType::STRING)]
    public const HOST = "log_logstash_host";
}
