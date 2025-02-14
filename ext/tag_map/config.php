<?php

declare(strict_types=1);

namespace Shimmie2;

class TagMapConfig extends ConfigGroup
{
    #[ConfigMeta("Show tags used at least N times", ConfigType::INT)]
    public const TAGS_MIN = "tags_min";

    #[ConfigMeta("Paged tag lists", ConfigType::BOOL)]
    public const PAGES = "tag_list_pages";
}
