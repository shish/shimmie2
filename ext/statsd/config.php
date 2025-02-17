<?php

declare(strict_types=1);

namespace Shimmie2;

class StatsDInterfaceConfig extends ConfigGroup
{
    public const KEY = "statsd";

    #[ConfigMeta("Host", ConfigType::STRING, default: "telegraf:8125")]
    public const HOST = "statsd_host";
}
