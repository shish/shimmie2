<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogConsoleConfig extends ConfigGroup
{
    public const KEY = "log_console";

    #[ConfigMeta("Log HTTP requests", ConfigType::BOOL, default: true)]
    public const LOG_ACCESS = "log_console_access";

    #[ConfigMeta("Use colour", ConfigType::BOOL, default: true)]
    public const COLOUR = "log_console_colour";

    #[ConfigMeta("Log level", ConfigType::INT, default: LogLevel::INFO->value, options: "Shimmie2\LogLevel::names_to_levels")]
    public const LEVEL = "log_console_level";
}
