<?php

declare(strict_types=1);

namespace Shimmie2;

class ResolutionLimitConfig extends ConfigGroup
{
    #[ConfigMeta("Min width", ConfigType::INT)]
    public const MIN_WIDTH = "upload_min_width";
    #[ConfigMeta("Min height", ConfigType::INT)]
    public const MIN_HEIGHT = "upload_min_height";
    #[ConfigMeta("Max width", ConfigType::INT)]
    public const MAX_WIDTH = "upload_max_width";
    #[ConfigMeta("Max height", ConfigType::INT, help: "-1 for no limit")]
    public const MAX_HEIGHT = "upload_max_height";
    #[ConfigMeta("Ratios", ConfigType::STRING, help: "eg. '4:3 16:9', blank for no limit")]
    public const RATIOS = "upload_ratios";
}
