<?php

declare(strict_types=1);

namespace Shimmie2;

final class CronUploaderUserConfig extends UserConfigGroup
{
    public const KEY = "cron_uploader";

    #[ConfigMeta("Root dir", ConfigType::STRING, permission: CronUploaderPermission::CRON_ADMIN)]
    public const DIR = "cron_uploader_dir";

    #[ConfigMeta("Stop on error", ConfigType::BOOL, default: false, permission: CronUploaderPermission::CRON_ADMIN)]
    public const STOP_ON_ERROR = "cron_uploader_stop_on_error";

    #[ConfigMeta("Include all logs", ConfigType::BOOL, default: false, permission: CronUploaderPermission::CRON_ADMIN)]
    public const INCLUDE_ALL_LOGS = "cron_uploader_include_all_logs";

    #[ConfigMeta("Log level", ConfigType::INT, default: LogLevel::INFO->value, options: "Shimmie2\LogLevel::names_to_levels", permission: CronUploaderPermission::CRON_ADMIN)]
    public const LOG_LEVEL = "cron_uploader_log_level";

    public function tweak_html(\MicroHTML\HTMLElement $html): \MicroHTML\HTMLElement
    {
        return \MicroHTML\emptyHTML(
            $html,
            \MicroHTML\A(["href" => make_link("cron_upload")], "Read the documentation"),
            " for cron setup instructions.",
        );
    }
}
