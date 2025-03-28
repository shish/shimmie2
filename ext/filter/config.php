<?php

declare(strict_types=1);

namespace Shimmie2;

final class FilterConfig extends ConfigGroup
{
    public const KEY = "filter";

    #[ConfigMeta(
        "Default filtered tags",
        ConfigType::STRING,
        input: ConfigInput::TEXTAREA,
        default: "spoilers\nguro\nscat\nfurry -rating:s\n",
        help: "This controls the tags which are hidden by default. This feature currently requires JavaScript. Separate filters by line, or by commas. You can enter multiple tags per filter, as well as negative tags."
    )]
    public const TAGS = "filter_tags";
}

final class FilterUserConfig extends UserConfigGroup
{
    public const KEY = "filter";

    #[ConfigMeta(
        "Default filtered tags",
        ConfigType::STRING,
        input: ConfigInput::TEXTAREA,
        help: "This controls the tags which are hidden by default. This feature currently requires JavaScript. Separate filters by line, or by commas. You can enter multiple tags per filter, as well as negative tags."
    )]
    public const TAGS = "filter_tags";
}
