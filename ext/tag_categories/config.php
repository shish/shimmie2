<?php

declare(strict_types=1);

namespace Shimmie2;

class TagCategoriesConfig extends ConfigGroup
{
    public const KEY = "tag_categories";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_tag_categories_version";

    #[ConfigMeta("Split on view", ConfigType::BOOL)]
    public const SPLIT_ON_VIEW = "tag_categories_split_on_view";
}
