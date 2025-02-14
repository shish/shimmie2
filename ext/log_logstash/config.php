<?php

declare(strict_types=1);

namespace Shimmie2;

class LogLogstashConfig extends ConfigGroup
{
    #[ConfigMeta("host:port", ConfigType::STRING)]
    public const HOST = "log_logstash_host";
}
