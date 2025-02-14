<?php

declare(strict_types=1);

namespace Shimmie2;

class BlotterConfig extends ConfigGroup
{
    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "blotter_version";

    #[ConfigMeta("Recent updates", ConfigType::INT)]
    public const RECENT = "blotter_recent";

    #[ConfigMeta("Important updates", ConfigType::STRING, ui_type: "color")]
    public const COLOR = "blotter_color";

    #[ConfigMeta("Position", ConfigType::STRING, options: [
        "Top of page" => "subheading",
        "In navigation bar" => "left"
    ])]
    public const POSITION = "blotter_position";
}
