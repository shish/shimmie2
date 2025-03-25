<?php

declare(strict_types=1);

namespace Shimmie2;

final class LiveFeedConfig extends ConfigGroup
{
    public const KEY = "livefeed";

    #[ConfigMeta("IP:port to send events to", ConfigType::STRING, default: "127.0.0.1:25252")]
    public const HOST = "livefeed_host";
}
