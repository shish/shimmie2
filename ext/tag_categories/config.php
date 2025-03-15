<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagCategoriesConfig extends ConfigGroup
{
    public const KEY = "tag_categories";

    // whether we split out separate categories on post view by default
    //  note: only takes effect if /post/view shows the image's exact tags
    #[ConfigMeta("Split on view", ConfigType::BOOL, default: true)]
    public const SPLIT_ON_VIEW = "tag_categories_split_on_view";
}
