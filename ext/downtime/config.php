<?php

declare(strict_types=1);

namespace Shimmie2;

class DowntimeConfig extends ConfigGroup
{
    #[ConfigMeta("Disable non-admin access", ConfigType::BOOL)]
    public const DOWNTIME = "downtime";

    #[ConfigMeta("Message for users", ConfigType::STRING, ui_type: "longtext")]
    public const DOWNTIME_MESSAGE = "downtime_message";
}
