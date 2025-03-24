<?php

declare(strict_types=1);

namespace Shimmie2;

final class LinkScanConfig extends ConfigGroup
{
    public const KEY = "link_scan";

    #[ConfigMeta("Trigger", ConfigType::STRING, default: "https?://", advanced: true)]
    public const TRIGGER = "link_scan_trigger";
}
