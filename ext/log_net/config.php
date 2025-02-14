<?php

declare(strict_types=1);

namespace Shimmie2;

class LogNetConfig extends ConfigGroup
{
    public const KEY = "log_net";

    #[ConfigMeta("host:port", ConfigType::STRING)]
    public const HOST = "log_net_host";
}
