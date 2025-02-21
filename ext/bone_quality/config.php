<?php

declare(strict_types=1);

namespace Shimmie2;

class BoneQualityConfig extends ConfigGroup
{
    public const KEY = "bone_quality";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_bone_quality_version";

    #[ConfigMeta("Failure string", ConfigType::STRING, default: "boned")]
    public const FAILURE_STRING = "bone_quality_failure_string";

    #[ConfigMeta("Chore searches", ConfigType::STRING, ui_type: "longtext", default: "tags:<5\ntagme\nartist_request\ntranslation_request", help: "newline separated")]
    public const CHORE_SEARCHES = "bone_quality_chore_searches";

    #[ConfigMeta("Chore threshold", ConfigType::INT, default: 20)]
    public const CHORE_THRESHOLD = "bone_quality_chore_threshold";
}
