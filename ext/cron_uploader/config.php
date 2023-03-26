<?php

declare(strict_types=1);

namespace Shimmie2;

abstract class CronUploaderConfig
{
    public const DEFAULT_PATH = "cron_uploader";

    public const DIR = "cron_uploader_dir";
    public const STOP_ON_ERROR = "cron_uploader_stop_on_error";
    public const INCLUDE_ALL_LOGS = "cron_uploader_include_all_logs";
    public const LOG_LEVEL = "cron_uploader_log_level";
}
