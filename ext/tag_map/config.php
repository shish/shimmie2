<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagMapConfig extends ConfigGroup
{
    public const KEY = "tag_map";

    #[ConfigMeta("Show tags used at least N times", ConfigType::INT, default: 3)]
    public const TAGS_MIN = "tags_min";

    #[ConfigMeta("Paged tag lists", ConfigType::BOOL, default: false)]
    public const PAGES = "tag_list_pages";
}
