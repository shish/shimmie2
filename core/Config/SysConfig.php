<?php

declare(strict_types=1);

namespace Shimmie2;

/**
* For any values that aren't defined in data/config/*.php,
* Shimmie will set the values to their defaults
*
* All of these can be over-ridden by placing a 'define' in
* data/config/shimmie.conf.php.
*
* Do NOT change them in this file. These are the defaults only!
*
* Example:
*  define("DEBUG", true);
*/
class SysConfig
{
    public static function getDatabaseDsn(): string
    {
        if (defined("DATABASE_DSN")) {
            return constant("DATABASE_DSN");
        }
        throw new \Exception("DATABASE_DSN is not set");
    }

    public static function getDatabaseTimeout(): int
    {
        return defined("DATABASE_TIMEOUT") ? constant("DATABASE_TIMEOUT") : 10000;
    }

    public static function getCacheDsn(): ?string
    {
        return defined("CACHE_DSN") ? constant("CACHE_DSN") : null;
    }

    public static function getDebug(): bool
    {
        return defined("DEBUG") ? constant("DEBUG") : false;
    }

    public static function getCookiePrefix(): string
    {
        return defined("COOKIE_PREFIX") ? constant("COOKIE_PREFIX") : 'shm';
    }

    public static function getWarehouseSplits(): int
    {
        return defined("WH_SPLITS") ? constant("WH_SPLITS") : 1;
    }

    public static function getVersion(bool $full = true): string
    {
        $ver = defined("VERSION") ? constant("VERSION") : "2.12.0-alpha";
        $time = defined("BUILD_TIME") ? substr(str_replace("-", "", constant("BUILD_TIME")), 0, 8) : null;
        $hash = defined("BUILD_HASH") ? substr(constant("BUILD_HASH"), 0, 7) : null;
        $git = file_exists(".git") ? "git" : null;

        if ($full) {
            return $ver . ($time ? "-$time" : "") . ($hash ? "-$hash" : "") . ($git ? "-$git" : "");
        }
        return $ver;
    }

    public static function getTimezone(): ?string
    {
        return defined("TIMEZONE") ? constant("TIMEZONE") : null;
    }

    public static function getExtraExtensions(): string
    {
        return defined("EXTRA_EXTS") ? constant("EXTRA_EXTS") : "";
    }

    public static function getBaseHref(): ?string
    {
        return defined("BASE_HREF") ? constant("BASE_HREF") : null;
    }

    public static function getTraceFile(): ?string
    {
        return defined("TRACE_FILE") ? constant("TRACE_FILE") : null;
    }

    public static function getTraceThreshold(): float
    {
        return defined("TRACE_THRESHOLD") ? constant("TRACE_THRESHOLD") : 0.0;
    }

    /**
     * @return array<string>
     */
    public static function getTrustedProxies(): array
    {
        return defined("TRUSTED_PROXIES") ? constant("TRUSTED_PROXIES") : [];
    }

    public static function getSecret(): string
    {
        if (defined("SECRET")) {
            return constant("SECRET");
        }
        return self::getDatabaseDsn();
    }
}
