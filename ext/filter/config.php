<?php

declare(strict_types=1);

namespace Shimmie2;

class FilterConfig extends ConfigGroup
{
    #[ConfigMeta(
        "Default filtered tags",
        ConfigType::STRING,
        ui_type: "longtext",
        help: "This controls the tags which are hidden by default. This feature currently requires JavaScript. Separate filters by line, or by commas. You can enter multiple tags per filter, as well as negative tags."
    )]
    public const TAGS = "filter_tags";
}

class FilterUserConfig extends ConfigGroup
{
    #[ConfigMeta(
        "Default filtered tags",
        ConfigType::STRING,
        ui_type: "longtext",
        help: "This controls the tags which are hidden by default. This feature currently requires JavaScript. Separate filters by line, or by commas. You can enter multiple tags per filter, as well as negative tags."
    )]
    public const TAGS = "filter_tags";
}
