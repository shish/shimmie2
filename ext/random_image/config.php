<?php

declare(strict_types=1);

namespace Shimmie2;

class RandomImageConfig extends ConfigGroup
{
    #[ConfigMeta("Show random block", ConfigType::BOOL)]
    public const SHOW_RANDOM_BLOCK = "show_random_block";
}
