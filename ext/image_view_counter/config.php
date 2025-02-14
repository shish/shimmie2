<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageViewCounterConfig extends ConfigGroup
{
    public const KEY = "image_view_counter";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = 'ext_image_view_counter';
}
