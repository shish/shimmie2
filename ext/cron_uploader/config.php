<?php

declare(strict_types=1);

namespace Shimmie2;

class CronUploaderUserConfig extends UserConfigGroup
{
    public const KEY = "cron_uploader";

    #[ConfigMeta("Default Path", ConfigType::STRING, advanced: true, permission: Permissions::CRON_ADMIN)]
    public const DEFAULT_PATH = "cron_uploader";

    #[ConfigMeta("Root dir", ConfigType::STRING, permission: Permissions::CRON_ADMIN)]
    public const DIR = "cron_uploader_dir";

    #[ConfigMeta("Stop on error", ConfigType::BOOL, permission: Permissions::CRON_ADMIN)]
    public const STOP_ON_ERROR = "cron_uploader_stop_on_error";

    #[ConfigMeta("Include all logs", ConfigType::BOOL, permission: Permissions::CRON_ADMIN)]
    public const INCLUDE_ALL_LOGS = "cron_uploader_include_all_logs";

    #[ConfigMeta("Log level", ConfigType::STRING, options: "Shimmie2\LogLevel::names_to_levels", permission: Permissions::CRON_ADMIN)]
    public const LOG_LEVEL = "cron_uploader_log_level";

    public function tweak_html(\MicroHTML\HTMLElement $html): \MicroHTML\HTMLElement
    {
        return \MicroHTML\emptyHTML(
            $html,
            \MicroHTML\A(["href" => make_http(make_link("cron_upload"))], "Read the documentation"),
            " for cron setup instructions.",
        );
    }
}
