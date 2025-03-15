<?php

declare(strict_types=1);

namespace Shimmie2;

final class DowntimeConfig extends ConfigGroup
{
    public const KEY = "downtime";

    #[ConfigMeta("Disable non-admin access", ConfigType::BOOL)]
    public const DOWNTIME = "downtime";

    #[ConfigMeta("Message for users", ConfigType::STRING, input: "longtext")]
    public const MESSAGE = "downtime_message";
}
