<?php

declare(strict_types=1);

namespace Shimmie2;

class TranscodeConfig extends ConfigGroup
{
    public ?string $title = "Transcode Images";

    #[ConfigMeta("Version", ConfigType::INT, advanced: true)]
    public const VERSION = "ext_transcode_version";

    #[ConfigMeta("Allow transcoding images", ConfigType::BOOL)]
    public const ENABLED = "transcode_enabled";

    #[ConfigMeta("Enable GET args", ConfigType::BOOL)]
    public const GET_ENABLED = "transcode_get_enabled";

    #[ConfigMeta("Transcode on upload", ConfigType::BOOL)]
    public const UPLOAD = "transcode_upload";

    #[ConfigMeta("Engine", ConfigType::STRING, options: ["GD" => "gd", "ImageMagick" => "convert"])]
    public const ENGINE = "transcode_engine";

    #[ConfigMeta("Lossy Format Quality", ConfigType::INT)]
    public const QUALITY = "transcode_quality";

    #[ConfigMeta("Alpha Conversion Color", ConfigType::STRING, ui_type: "color")]
    public const ALPHA_COLOR = "transcode_alpha_color";
}
