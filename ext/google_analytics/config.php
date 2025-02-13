<?php

declare(strict_types=1);

namespace Shimmie2;

class GoogleAnalyticsConfig extends ConfigGroup
{
    #[ConfigMeta("Analytics ID", ConfigType::STRING, help: "eg. UA-xxxxxxxx-x")]
    public const ANALYTICS_ID = "google_analytics_id";
}
