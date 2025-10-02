<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Various very-internal settings which can be customised by
 * defining constants in `data/config/shimmie.conf.php`, eg
 *
 *     define("DATABASE_DSN", "sqlite:data/db.sqlite");
 *     define("CACHE_DSN", "apc://");
 *     define("DEBUG", true);
 *
 * Normally you shouldn't need to set any of these manually,
 * as Shimmie will auto-detect sensible defaults.
 */
final class SysConfig
{
    /**
     * Path to the database, in PDO DSN format, eg
     * "sqlite:data/db.sqlite" or "mysql:host=localhost;dbname=shimmie"
     *
     * This is the only setting which needs to be set, and it should
     * already be set by the installer.
     */
    public static function getDatabaseDsn(): string
    {
        if (defined("DATABASE_DSN")) {
            return constant("DATABASE_DSN");
        }
        throw new \Exception("DATABASE_DSN is not set");
    }

    /**
     * How long an invididual database query should be allowed to run,
     * in milliseconds. Note that some admin function will override this
     * to a higher value during specific high-load operations.
     */
    public static function getDatabaseTimeout(): int
    {
        return defined("DATABASE_TIMEOUT") ? constant("DATABASE_TIMEOUT") : 10000;
    }

    /**
     * Path to the cache, in URL format (the "DSN" is a legacy thing),
     * eg "apc://", "memcached://localhost:11211", or "redis://localhost:6379".
     */
    public static function getCacheDsn(): ?string
    {
        return defined("CACHE_DSN") ? constant("CACHE_DSN") : null;
    }

    /**
     * Settings WH_SPLITS to 2 will store files as `images/ab/cd/...`
     * instead of `images/ab/...`, which can reduce filesystem load
     * when you have millions of images. Note that if you use this
     * setting, you'll need to update the webserver config to match
     * (or if using the docker image, just restart the container and
     * it will automatically reconfigure the internal webserver)
     */
    public static function getWarehouseSplits(): int
    {
        return defined("WH_SPLITS") ? constant("WH_SPLITS") : 1;
    }

    /**
     * Manually set the timezone, to ensure that PHP and the database
     * agree on what time it is.
     */
    public static function getTimezone(): ?string
    {
        return defined("TIMEZONE") ? constant("TIMEZONE") : null;
    }

    /**
     * Manually set the location for the shimmie install, in case
     * auto-detection can't figure it out for some reason. For example,
     * if your shimmie install is at `https://example.com/gallery/`,
     * set this to `/gallery`.
     */
    public static function getBaseHref(): ?string
    {
        return defined("BASE_HREF") ? constant("BASE_HREF") : null;
    }

    /**
     * If shimmie is running behind a reverse proxy (eg nginx),
     * and the proxy passes the original client IP in a header
     * (eg `X-Forwarded-For`), set this to the IP addresses of
     * the proxy so that shimmie can correctly determine the
     * real client IP.
     *
     * This can be a single IP address, or a CIDR range (eg.
     * `["10.0.0.0/8", "172.16.0.0/12", "192.168.0.0/16"]`
     * to trust all local addresses).
     *
     * @return array<string>
     */
    public static function getTrustedProxies(): array
    {
        return defined("TRUSTED_PROXIES") ? constant("TRUSTED_PROXIES") : [];
    }

    /**
     * A secret string used for hashing things like session cookies
     * and CSRF tokens. If not set, it defaults to the database DSN,
     * which isn't ideal but is better than nothing. Newer versions
     * of the installer should set this automatically.
     */
    public static function getSecret(): string
    {
        if (defined("SECRET")) {
            return constant("SECRET");
        }
        return self::getDatabaseDsn();
    }

    /**
     * Get the current version of Shimmie - these should be set as part
     * of the build/release process, and shouldn't be changed manually.
     */
    public static function getVersion(bool $full = true): string
    {
        $ver = defined("VERSION") ? constant("VERSION") : "2.12.0-beta";
        $time = defined("BUILD_TIME") ? substr(str_replace("-", "", constant("BUILD_TIME")), 0, 8) : null;
        $hash = defined("BUILD_HASH") ? substr(constant("BUILD_HASH"), 0, 7) : null;
        $git = file_exists(".git") ? "git" : null;

        if ($full) {
            return $ver . ($time ? "-$time" : "") . ($hash ? "-$hash" : "") . ($git ? "-$git" : "");
        }
        return $ver;
    }

    /**
     * Get a list of which non-core extensions are enabled. This setting
     * should normally be set in `data/config/extensions.conf.php`, which
     * is created automatically when you enable or disable extensions
     * via the admin interface.
     *
     * @return array<string>
     */
    public static function getExtraExtensions(): array
    {
        // Up to 2.11 uses a comma-separated string,
        // but since 2.12 it's an array
        if (defined("EXTRA_EXTS")) {
            $v = constant("EXTRA_EXTS");
            if (is_array($v)) {
                /** @var array<string> $v */
                return $v;
            }
            if (is_string($v)) {
                if ($v === "") {
                    return [];
                } else {
                    return explode(",", $v);
                }
            }
        }
        return [];
    }
}
