<?php

declare(strict_types=1);

namespace Shimmie2;

final class RandomImageConfig extends ConfigGroup
{
    public const KEY = "random_image";

    #[ConfigMeta("Show random block", ConfigType::BOOL)]
    public const SHOW_RANDOM_BLOCK = "show_random_block";
}
