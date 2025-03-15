<?php

declare(strict_types=1);

namespace Shimmie2;

final class StatsDInterfaceConfig extends ConfigGroup
{
    public const KEY = "statsd";
    public ?string $title = "StatsD";

    #[ConfigMeta("Host", ConfigType::STRING)]
    public const HOST = "statsd_host";
}
