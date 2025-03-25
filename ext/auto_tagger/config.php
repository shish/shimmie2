<?php

declare(strict_types=1);

namespace Shimmie2;

final class AutoTaggerConfig extends ConfigGroup
{
    public const KEY = "auto_tagger";

    #[ConfigMeta("Items per page", ConfigType::INT, default: 30)]
    public const ITEMS_PER_PAGE = "auto_tagger_items_per_page";
}
