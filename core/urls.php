<?php declare(strict_types=1);
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* HTML Generation                                                           *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Link
{
    public $page;
    public $query;

    public function __construct(?string $page=null, ?string $query=null)
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
 * Figure out the correct way to link to a page, taking into account
 * things like the nice URLs setting.
 *
 * eg make_link("post/list") becomes "/v2/index.php?q=post/list"
 */
function make_link(?string $page=null, ?string $query=null): string
{
    global $config;

    if (is_null($page)) {
        $page = $config->get_string(SetupConfig::MAIN_PAGE);
    }

    $install_dir = get_base_href();
    if (SPEED_HAX || $config->get_bool('nice_urls', false)) {
        $base = $install_dir;
    } else {
        $base = "$install_dir/index.php?q=";
    }

    if (is_null($query)) {
        return str_replace("//", "/", $base.'/'.$page);
    } else {
        if (strpos($base, "?")) {
            return $base .'/'. $page .'&'. $query;
        } elseif (strpos($query, "#") === 0) {
            return $base .'/'. $page . $query;
        } else {
            return $base .'/'. $page .'?'. $query;
        }
    }
}


/**
 * Take the current URL and modify some parameters
 */
function modify_current_url(array $changes): string
{
    return modify_url($_SERVER['QUERY_STRING'], $changes);
}

function modify_url(string $url, array $changes): string
{
    // SHIT: PHP is officially the worst web API ever because it does not
    // have a built-in function to do this.

    // SHIT: parse_str is magically retarded; not only is it a useless name, it also
    // didn't return the parsed array, preferring to overwrite global variables with
    // whatever data the user supplied. Thankfully, 4.0.3 added an extra option to
    // give it an array to use...
    $params = [];
    parse_str($url, $params);

    if (isset($changes['q'])) {
        $base = $changes['q'];
        unset($changes['q']);
    } else {
        $base = _get_query();
    }

    if (isset($params['q'])) {
        unset($params['q']);
    }

    foreach ($changes as $k => $v) {
        if (is_null($v) and isset($params[$k])) {
            unset($params[$k]);
        }
        $params[$k] = $v;
    }

    return make_link($base, http_build_query($params));
}


/**
 * Turn a relative link into an absolute one, including hostname
 */
function make_http(string $link): string
{
    if (strpos($link, "://") > 0) {
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

function referer_or(string $dest): string
{
    return $_SERVER['HTTP_REFERER'] ?? $dest;
}
