<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\Type;
use GQLA\Field;

/**
 * A fairly standard URL class with a couple of Shimmie-specific helpers:
 *
 * - you can pass `page` rather than `path` to generate a valid link to
 *   a shimmie page, eg page:"post/list" becomes either /post/list or
 *   /index.php?q=post/list as appropriate.
 * - forming a URL with a page _and_ a path at the same time is invalid
 */
#[Type(name: "Url")]
final class Url
{
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
    * Various constructors                                                  *
    \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * @param ?url-string $path
     * @param ?page-string $page
     * @param ?query-array $query
     * @param ?fragment-string $fragment
     */
    public function __construct(
        private ?string $scheme = null,
        private ?string $user = null,
        private ?string $pass = null,
        private ?string $host = null,
        private ?int $port = null,
        private ?string $page = null,
        private ?string $path = null,
        private ?array $query = null,
        private ?string $fragment = null
    ) {
        assert($page === null || $path === null);
        if ($page !== null && str_starts_with($page, "/")) {
            throw new \InvalidArgumentException("Url(page: $page): page cannot start with a slash");
        }

    }

    public static function parse(string $url): Url
    {
        $parsed = parse_url($url);

        $query_array = null;
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query_array);
        }

        /** @var query-array $query_array */

        return new Url(
            scheme: $parsed['scheme'] ?? null,
            user: $parsed['user'] ?? null,
            pass: $parsed['pass'] ?? null,
            host: $parsed['host'] ?? null,
            port: $parsed['port'] ?? null,
            path: $parsed['path'] ?? null,
            query: $query_array,
            fragment: $parsed['fragment'] ?? null
        );
    }

    public static function current(): Url
    {
        return self::parse($_SERVER['REQUEST_URI']);
    }

    /**
     * Figure out the path to the shimmie install directory.
     *
     * eg if shimmie is visible at https://foo.com/gallery, this
     * function should return /gallery
     *
     * PHP really, really sucks.
     *
     * This function should always return strings with no trailing
     * slashes, so that it can be used like `Url::base() . "/data/asset.abc"`
     *
     * @param array<string, string>|null $server_settings
     */
    public static function base(?array $server_settings = null): Url
    {
        if (!is_null(SysConfig::getBaseHref())) {
            $dir = SysConfig::getBaseHref();
        } else {
            $server_settings = $server_settings ?? $_SERVER;
            if (str_ends_with($server_settings['PHP_SELF'], 'index.php')) {
                $self = $server_settings['PHP_SELF'];
            } elseif (isset($server_settings['SCRIPT_FILENAME']) && isset($server_settings['DOCUMENT_ROOT'])) {
                $self = substr($server_settings['SCRIPT_FILENAME'], strlen(rtrim($server_settings['DOCUMENT_ROOT'], "/")));
            } else {
                die("PHP_SELF or SCRIPT_FILENAME need to be set");
            }
            $dir = dirname($self);
            $dir = str_replace("\\", "/", $dir);
            $dir = rtrim($dir, "/");
        }
        if (empty($dir)) {
            $dir = null;
        }
        return new Url(path: $dir);
    }

    /**
     * If HTTP_REFERER is set, and doesn't contain anything in the ignore
     * list, then return it, else return a default $dest
     *
     * @param string[] $ignore
     */
    public static function referer_or(?Url $dest = null, array $ignore = []): Url
    {
        $dest ??= new Url(page: "");

        /** @var ?url-string $referer */
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if (is_null($referer)) {
            return $dest;
        }
        foreach ($ignore as $b) {
            if (str_contains($referer, $b)) {
                return $dest;
            }
        }
        return Url::parse($referer);
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
    * Attribute getters                                                     *
    \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function getPage(): ?string
    {
        return $this->page;
    }

    public function getPath(): string
    {
        global $config;
        assert(is_null($this->path) || is_null($this->page));
        if ($this->path !== null) {
            assert(str_starts_with($this->path, "/"));
            $path = $this->path;
        } elseif ($this->page !== null) {
            /**
             * Figure out the correct way to link to a page, taking into account
             * things like the nice URLs setting.
             *
             * eg make_link("foo/bar") becomes either "/v2/foo/bar" (niceurls) or
             * "/v2/index.php?q=foo/bar" (uglyurls)
             */
            $install_dir = (string)Url::base();
            if ($config->get_bool(SetupConfig::NICE_URLS, false)) {
                $path = "$install_dir/{$this->page}";
            } else {
                $path = "$install_dir/index.php?q={$this->page}";
            }
        } else {
            $path = '';
        }
        return $path;
    }

    /**
     * Get the query parameters as an array.
     *
     * @return query-array The query parameters.
     */
    public function getQueryArray(): array
    {
        return $this->query ?? [];
    }

    #[Field(name: "url")]
    public function __toString(): string
    {
        global $config;

        $scheme   = !is_null($this->scheme) ? $this->scheme . '://' : '';
        $host     = $this->host ?? '';
        $port     = !is_null($this->port) ? ':' . $this->port : '';
        $user     = $this->user ?? '';
        $pass     = !is_null($this->pass) ? ':' . $this->pass : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $this->getPath();

        if (!empty($this->query)) {
            $query_joiner = $config->get_bool(SetupConfig::NICE_URLS) ? '?' : '&';
            $query        = $query_joiner . http_build_query($this->query);
        } else {
            $query = '';
        }

        $fragment = !empty($this->fragment) ? '#' . $this->fragment : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
    * Return a modified version of this URL                                 *
    \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    public function asAbsolute(): Url
    {
        return new Url(
            scheme: $this->scheme ?? self::is_https_enabled() ? "https" : "http",
            user: $this->user,
            pass: $this->pass,
            host: $this->host ?? $_SERVER["HTTP_HOST"],
            port: $this->port,
            page: $this->page,
            path: $this->path,
            query: $this->query,
            fragment: $this->fragment
        );
    }

    /**
     * @param array<string, string|null> $changes
     */
    public function withModifiedQuery(array $changes): Url
    {
        $query = $this->query ?? [];
        foreach ($changes as $k => $v) {
            if (is_null($v) and isset($query[$k])) {
                unset($query[$k]);
            } elseif (!is_null($v)) {
                $query[$k] = $v;
            }
        }
        if (empty($query)) {
            $query = null;
        }

        return new Url(
            scheme: $this->scheme,
            user: $this->user,
            pass: $this->pass,
            host: $this->host,
            port: $this->port,
            page: $this->page,
            path: $this->path,
            query: $query,
            fragment: $this->fragment
        );
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
    * Misc URL utilities                                                    *
    \* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Check if HTTPS is enabled for the server.
     */
    public static function is_https_enabled(): bool
    {
        // check forwarded protocol
        if (Network::is_trusted_proxy() && !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
        }
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    }
}
