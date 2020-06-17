<?php declare(strict_types=1);


abstract class CronUploaderConfig
{
    public const DEFAULT_PATH = "cron_uploader";

    public const KEY = "cron_uploader_key";
    public const DIR = "cron_uploader_dir";
    public const USER = "cron_uploader_user";
    public const STOP_ON_ERROR = "cron_uploader_stop_on_error";
    public const INCLUDE_ALL_LOGS = "cron_uploader_include_all_logs";
    public const LOG_LEVEL = "cron_uploader_log_level";

    public static function set_defaults(): void
    {
        global $config;
        $config->set_default_string(self::DIR, data_path(self::DEFAULT_PATH));
        $config->set_default_bool(self::INCLUDE_ALL_LOGS, false);
        $config->set_default_bool(self::STOP_ON_ERROR, false);
        $config->set_default_int(self::LOG_LEVEL, SCORE_LOG_INFO);
        $upload_key = $config->get_string(self::KEY, "");
        if (empty($upload_key)) {
            $upload_key = generate_key();

            $config->set_string(self::KEY, $upload_key);
        }
    }

    public static function get_user(): int
    {
        global $config;
        return $config->get_int(self::USER);
    }

    public static function set_user(int $value): void
    {
        global $config;
        $config->set_int(self::USER, $value);
    }

    public static function get_key(): string
    {
        global $config;
        return $config->get_string(self::KEY);
    }

    public static function set_key(string $value): void
    {
        global $config;
        $config->set_string(self::KEY, $value);
    }

    public static function get_dir(): string
    {
        global $config;
        $value = $config->get_string(self::DIR);
        if (empty($value)) {
            $value = data_path("cron_uploader");
            self::set_dir($value);
        }
        return $value;
    }

    public static function set_dir(string $value): void
    {
        global $config;
        $config->set_string(self::DIR, $value);
    }
}
