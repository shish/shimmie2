<?php

declare(strict_types=1);

namespace Shimmie2;

final class ResolutionLimitConfig extends ConfigGroup
{
    public const KEY = "res_limit";

    #[ConfigMeta("Min width", ConfigType::INT, default: -1)]
    public const MIN_WIDTH = "upload_min_width";
    #[ConfigMeta("Min height", ConfigType::INT, default: -1)]
    public const MIN_HEIGHT = "upload_min_height";
    #[ConfigMeta("Max width", ConfigType::INT, default: -1)]
    public const MAX_WIDTH = "upload_max_width";
    #[ConfigMeta("Max height", ConfigType::INT, default: -1, help: "-1 for no limit")]
    public const MAX_HEIGHT = "upload_max_height";
    #[ConfigMeta("Ratios", ConfigType::STRING, default: "", help: "eg. '4:3 16:9', blank for no limit")]
    public const RATIOS = "upload_ratios";
}
