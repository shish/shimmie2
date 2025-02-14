<?php

declare(strict_types=1);

namespace Shimmie2;

class ArchiveFileHandlerConfig extends ConfigGroup
{
    public const KEY = "handle_archive";

    #[ConfigMeta("Temp dir", ConfigType::STRING, advanced: true)]
    public const TMP_DIR = "archive_tmp_dir";

    #[ConfigMeta("Extraction command", ConfigType::STRING, help: "%f for archive, %d for temporary directory", advanced: true)]
    public const EXTRACT_COMMAND = "archive_extract_command";
}
