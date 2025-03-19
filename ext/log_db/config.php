<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogDatabaseConfig extends ConfigGroup
{
    public const KEY = "log_db";

    #[ConfigMeta("Log level", ConfigType::INT, default: LogLevel::INFO->value, options: "Shimmie2\LogLevel::names_to_levels")]
    public const LEVEL = "log_db_priority";
}
