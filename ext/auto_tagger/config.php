<?php

declare(strict_types=1);

namespace Shimmie2;

class AutoTaggerConfig extends ConfigGroup
{
    public const KEY = "auto_tagger";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_auto_tagger_ver";

    #[ConfigMeta("Items per page", ConfigType::INT)]
    public const ITEMS_PER_PAGE = "auto_tagger_items_per_page";
}
