<?php

declare(strict_types=1);

namespace Shimmie2;

final class MediaConfig extends ConfigGroup
{
    public const KEY = "media";

    #[ConfigMeta("Magick path", ConfigType::STRING, default: "magick")]
    public const MAGICK_PATH = "media_convert_path";

    #[ConfigMeta("Memory limit", ConfigType::INT, input: ConfigInput::BYTES, default: 8 * 1024 * 1024)]
    public const MEM_LIMIT = 'media_mem_limit';
}
