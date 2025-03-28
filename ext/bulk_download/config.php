<?php

declare(strict_types=1);

namespace Shimmie2;

final class BulkDownloadConfig extends ConfigGroup
{
    public const KEY = "bulk_download";

    #[ConfigMeta("Size limit", ConfigType::INT, input: ConfigInput::BYTES, default: 100 * 1024 * 1024)]
    public const SIZE_LIMIT = "bulk_download_size_limit";
}
