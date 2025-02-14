<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomListConfig extends ConfigGroup
{
    #[ConfigMeta("Posts to display", ConfigType::INT)]
    public const LIST_COUNT = 'random_images_list_count';
}
