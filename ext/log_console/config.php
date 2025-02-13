<?php

declare(strict_types=1);

namespace Shimmie2;

class LogConsoleConfig extends ConfigGroup
{
    #[ConfigMeta("Log HTTP requests", ConfigType::BOOL)]
    public const LOG_ACCESS = "log_console_access";

    #[ConfigMeta("Use colour", ConfigType::BOOL)]
    public const COLOUR = "log_console_colour";

    #[ConfigMeta("Log level", ConfigType::STRING, options: "Shimmie2\LogLevel::names_to_levels")]
    public const LEVEL = "log_console_level";
}
