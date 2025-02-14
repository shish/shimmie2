<?php

declare(strict_types=1);

namespace Shimmie2;

class LiveFeedConfig extends ConfigGroup
{
    public const KEY = "livefeed";

    #[ConfigMeta("IP:port to send events to", ConfigType::STRING)]
    public const HOST = "livefeed_host";
}
