<?php

declare(strict_types=1);

namespace Shimmie2;

final class ArchiveFileHandlerConfig extends ConfigGroup
{
    public const KEY = "handle_archive";

    #[ConfigMeta("Extraction command", ConfigType::STRING, default: 'unzip -d "%d" "%f"', help: "%f for archive, %d for temporary directory", advanced: true)]
    public const EXTRACT_COMMAND = "archive_extract_command";
}
