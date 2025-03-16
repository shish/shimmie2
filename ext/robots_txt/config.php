<?php

declare(strict_types=1);

namespace Shimmie2;

final class RobotsTxtConfig extends ConfigGroup
{
    public const KEY = "robots_txt";

    #[ConfigMeta("Canonical domain", ConfigType::STRING, default: null, advanced: true, help: "If set, requests to this site via other domains will be blocked")]
    public const CANONICAL_DOMAIN = "robots_txt_canonical_domain";
}
