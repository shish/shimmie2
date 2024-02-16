<?php

declare(strict_types=1);

namespace Shimmie2;

class Link
{
    public ?string $page;
    public ?string $query;

    public function __construct(?string $page = null, ?string $query = null)
    {
        $this->page = $page;
        $this->query = $query;
    }

    public function make_link(): string
    {
        return make_link($this->page, $this->query);
    }
}

/**
 * Build a link to a search page for given terms,
 * with all the appropriate escaping
 *
 * @param string[] $terms
 */
function search_link(array $terms = [], int $page = 1): string
{
    if($terms) {
        $q = url_escape(Tag::implode($terms));
        return make_link("post/list/$q/$page");
    } else {
        return make_link("post/list/$page");
    }
}

/**
 * Figure out the correct way to link to a page, taking into account
 * things like the nice URLs setting.
 *
 * eg make_link("foo/bar") becomes either "/v2/foo/bar" (niceurls) or
 * "/v2/index.php?q=foo/bar" (uglyurls)
 */
function make_link(?string $page = null, ?string $query = null, ?string $fragment = null): string
{
    global $config;

    if (is_null($page)) {
        $page = $config->get_string(SetupConfig::MAIN_PAGE);
    }
    $page = trim($page, "/");

    $parts = [];
    $install_dir = get_base_href();
    if (SPEED_HAX || $config->get_bool(SetupConfig::NICE_URLS, false)) {
        $parts['path'] = "$install_dir/$page";
    } else {
        $parts['path'] = "$install_dir/index.php";
        $query = empty($query) ? "q=$page" : "q=$page&$query";
    }
    $parts['query'] = $query;  // http_build_query($query);
    $parts['fragment'] = $fragment;  // http_build_query($hash);

    return unparse_url($parts);
}

/**
 * Figure out the current page from a link that make_link() generated
 *
 * SHIT: notes for the future, because the web stack is a pile of hacks
 *
 * - According to some specs, "/" is for URL dividers with heiracial
 *   significance and %2F is for slashes that are just slashes. This
 *   is what shimmie currently does - eg if you search for "AC/DC",
 *   the shimmie URL will be /post/list/AC%2FDC/1
 * - According to some other specs "/" and "%2F" are identical...
 * - PHP's $_GET[] automatically urldecodes the inputs so we can't
 *   tell the difference between q=foo/bar and q=foo%2Fbar
 * - REQUEST_URI contains the exact URI that was given to us, so we
 *   can parse it for ourselves
 * - <input type='hidden' name='q' value='post/list'> generates
 *   q=post%2Flist
 *
 * This function should always return strings with no leading slashes
 */
function _get_query(?string $uri = null): string
{
    $parsed_url = parse_url($uri ?? $_SERVER['REQUEST_URI'] ?? "");

    // if we're looking at http://site.com/$INSTALL_DIR/index.php,
    // then get the query from the "q" parameter
    if(($parsed_url["path"] ?? "") == (get_base_href() . "/index.php")) {
        // $q = $_GET["q"] ?? "";
        // default to looking at the root
        $q = "";
        // (we need to manually parse the query string because PHP's $_GET
        // does an extra round of URL decoding, which we don't want)
        foreach(explode('&', $parsed_url['query'] ?? "") as $z) {
            $qps = explode('=', $z, 2);
            if(count($qps) == 2 && $qps[0] == "q") {
                $q = $qps[1];
            }
        }
        // if we have no slashes, but do have an encoded
        // slash, then we _probably_ encoded too much
        if(!str_contains($q, "/") && str_contains($q, "%2F")) {
            $q = rawurldecode($q);
        }
    }

    // if we're looking at http://site.com/$INSTALL_DIR/$PAGE,
    // then get the query from the path
    else {
        $q = substr($parsed_url["path"] ?? "", strlen(get_base_href() . "/"));
    }

    assert(!str_starts_with($q, "/"));
    return $q;
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
 * slashes, so that it can be used like `get_base_href() . "/data/asset.abc"`
 *
 * @param array<string, string>|null $server_settings
 */
function get_base_href(?array $server_settings = null): string
{
    if (defined("BASE_HREF") && !empty(BASE_HREF)) {
        return BASE_HREF;
    }
    $server_settings = $server_settings ?? $_SERVER;
    if(str_ends_with($server_settings['PHP_SELF'], 'index.php')) {
        $self = $server_settings['PHP_SELF'];
    } elseif(isset($server_settings['SCRIPT_FILENAME']) && isset($server_settings['DOCUMENT_ROOT'])) {
        $self = substr($server_settings['SCRIPT_FILENAME'], strlen(rtrim($server_settings['DOCUMENT_ROOT'], "/")));
    } else {
        die("PHP_SELF or SCRIPT_FILENAME need to be set");
    }
    $dir = dirname($self);
    $dir = str_replace("\\", "/", $dir);
    $dir = rtrim($dir, "/");
    return $dir;
}

/**
 * The opposite of the standard library's parse_url
 *
 * @param array<string, string|int> $parsed_url
 */
function unparse_url(array $parsed_url): string
{
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = $parsed_url['host'] ?? '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = $parsed_url['user'] ?? '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = $parsed_url['path'] ?? '';
    $query    = !empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = !empty($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path$query$fragment";
}


/**
 * Take the current URL and modify some parameters
 *
 * @param array<string, mixed> $changes
 */
function modify_current_url(array $changes): string
{
    return modify_url($_SERVER['REQUEST_URI'], $changes);
}

/**
 * Take a URL and modify some parameters
 *
 * @param array<string, mixed> $changes
 */
function modify_url(string $url, array $changes): string
{
    /** @var array<string, mixed> */
    $parts = parse_url($url);

    $params = [];
    if (isset($parts['query'])) {
        parse_str($parts['query'], $params);
    }
    foreach ($changes as $k => $v) {
        if (is_null($v) and isset($params[$k])) {
            unset($params[$k]);
        }
        $params[$k] = $v;
    }
    $parts['query'] = http_build_query($params);

    return unparse_url($parts);
}

/**
 * Turn a relative link into an absolute one, including hostname
 */
function make_http(string $link): string
{
    if (str_contains($link, "://")) {
        return $link;
    }

    if (strlen($link) > 0 && $link[0] != '/') {
        $link = get_base_href() . '/' . $link;
    }

    $protocol = is_https_enabled() ? "https://" : "http://";
    $link = $protocol . $_SERVER["HTTP_HOST"] . $link;
    $link = str_replace("/./", "/", $link);

    return $link;
}

/**
 * If HTTP_REFERER is set, and not blacklisted, then return it
 * Else return a default $dest
 *
 * @param string[]|null $blacklist
 */
function referer_or(string $dest, ?array $blacklist = null): string
{
    if (empty($_SERVER['HTTP_REFERER'])) {
        return $dest;
    }
    if ($blacklist) {
        foreach ($blacklist as $b) {
            if (str_contains($_SERVER['HTTP_REFERER'], $b)) {
                return $dest;
            }
        }
    }
    return $_SERVER['HTTP_REFERER'];
}
