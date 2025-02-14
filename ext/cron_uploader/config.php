<?php

declare(strict_types=1);

namespace Shimmie2;

class CronUploaderUserConfig extends UserConfigGroup
{
    public const KEY = "cron_uploader";

    #[ConfigMeta("Default Path", ConfigType::STRING, advanced: true)]
    public const DEFAULT_PATH = "cron_uploader";

    #[ConfigMeta("Root dir", ConfigType::STRING)]
    public const DIR = "cron_uploader_dir";

    #[ConfigMeta("Stop on error", ConfigType::BOOL)]
    public const STOP_ON_ERROR = "cron_uploader_stop_on_error";

    #[ConfigMeta("Include all logs", ConfigType::BOOL)]
    public const INCLUDE_ALL_LOGS = "cron_uploader_include_all_logs";

    #[ConfigMeta("Log level", ConfigType::STRING, options: "Shimmie2\LogLevel::names_to_levels")]
    public const LOG_LEVEL = "cron_uploader_log_level";
}
