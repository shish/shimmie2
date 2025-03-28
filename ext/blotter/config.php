<?php

declare(strict_types=1);

namespace Shimmie2;

final class BlotterConfig extends ConfigGroup
{
    public const KEY = "blotter";

    #[ConfigMeta("Recent updates", ConfigType::INT, default: 5)]
    public const RECENT = "blotter_recent";

    #[ConfigMeta("Important updates", ConfigType::STRING, input: ConfigInput::COLOR, default: "#FF0000")]
    public const COLOR = "blotter_color";

    #[ConfigMeta("Position", ConfigType::STRING, default: "subheading", options: [
        "Top of page" => "subheading",
        "In navigation bar" => "left"
    ])]
    public const POSITION = "blotter_position";
}
