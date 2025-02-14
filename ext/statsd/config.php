<?php

declare(strict_types=1);

namespace Shimmie2;

class StatsDInterfaceConfig extends ConfigGroup
{
    #[ConfigMeta("Host", ConfigType::STRING)]
    public const HOST = "statsd_host";
}
