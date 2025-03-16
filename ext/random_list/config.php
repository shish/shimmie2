<?php

declare(strict_types=1);

namespace Shimmie2;

final class RandomListConfig extends ConfigGroup
{
    public const KEY = "random_list";

    #[ConfigMeta("Posts to display", ConfigType::INT, default: 12)]
    public const LIST_COUNT = 'random_images_list_count';
}
