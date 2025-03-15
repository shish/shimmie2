<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogNetConfig extends ConfigGroup
{
    public const KEY = "log_net";

    #[ConfigMeta("host:port", ConfigType::STRING, default: "127.0.0.1:35353")]
    public const HOST = "log_net_host";
}
