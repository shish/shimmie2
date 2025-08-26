<?php

declare(strict_types=1);

namespace Shimmie2;

final class TranscodeImageConfig extends ConfigGroup
{
    public const KEY = "transcode";
    public ?string $title = "Transcode Images";

    #[ConfigMeta("Enable GET args", ConfigType::BOOL, default: false)]
    public const GET_ENABLED = "transcode_get_enabled";

    #[ConfigMeta("Engine", ConfigType::STRING, default: 'gd', options: ["GD" => "gd", "ImageMagick" => "convert"])]
    public const ENGINE = "transcode_engine";

    #[ConfigMeta("Lossy format quality", ConfigType::INT, default: 80)]
    public const QUALITY = "transcode_quality";

    #[ConfigMeta("Alpha conversion color", ConfigType::STRING, default: "#00000000", input: ConfigInput::COLOR)]
    public const ALPHA_COLOR = "transcode_alpha_color";
}
