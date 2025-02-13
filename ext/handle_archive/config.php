<?php

declare(strict_types=1);

namespace Shimmie2;

class ArchiveFileHandlerConfig extends ConfigGroup
{
    #[ConfigMeta("Temp Dir", ConfigType::STRING, advanced: true)]
    public const TMP_DIR = "archive_tmp_dir";

    #[ConfigMeta("Extraction command", ConfigType::STRING, help: "%f for archive, %d for temporary directory")]
    public const EXTRACT_COMMAND = "archive_extract_command";
}
