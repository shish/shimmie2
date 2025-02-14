<?php

declare(strict_types=1);

namespace Shimmie2;

class BulkDownloadConfig extends ConfigGroup
{
    #[ConfigMeta("Size limit", ConfigType::INT, ui_type: "shorthand_int")]
    public const SIZE_LIMIT = "bulk_download_size_limit";
}
