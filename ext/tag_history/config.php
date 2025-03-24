<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagHistoryConfig extends ConfigGroup
{
    public const KEY = "tag_history";

    #[ConfigMeta("Max History", ConfigType::INT, default: -1, advanced: true)]
    public const MAX_HISTORY = "tag_history_max_history";
}
