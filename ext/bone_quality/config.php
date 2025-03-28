<?php

declare(strict_types=1);

namespace Shimmie2;

final class BoneQualityConfig extends ConfigGroup
{
    public const KEY = "bone_quality";

    #[ConfigMeta("Failure word", ConfigType::STRING, default: "boned")]
    public const FAILURE_STRING = "bone_quality_failure_string";

    #[ConfigMeta("Chore searches (newline separated)", ConfigType::STRING, default: "tags:<5\ntagme\nartist_request\ntranslation_request", input: ConfigInput::TEXTAREA)]
    public const CHORE_SEARCHES = "bone_quality_chore_searches";

    #[ConfigMeta("Chore search threshold", ConfigType::INT, default: 20)]
    public const CHORE_THRESHOLD = "bone_quality_chore_threshold";
}
