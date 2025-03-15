<?php

declare(strict_types=1);

namespace Shimmie2;

final class SiteDescriptionConfig extends ConfigGroup
{
    public const KEY = "site_description";

    #[ConfigMeta("Description", ConfigType::STRING)]
    public const DESCRIPTION = "site_description";

    #[ConfigMeta("Keywords", ConfigType::STRING)]
    public const KEYWORDS = "site_keywords";
}
