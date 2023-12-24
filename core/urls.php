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
 * eg make_link("foo/bar") becomes "/v2/index.php?q=foo/bar"
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
    if (SPEED_HAX || $config->get_bool('nice_urls', false)) {
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
 * Take the current URL and modify some parameters
 */
function modify_current_url(array $changes): string
{
    return modify_url($_SERVER['REQUEST_URI'], $changes);
}

function modify_url(string $url, array $changes): string
{
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
