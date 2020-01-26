<?php declare(strict_types=1);
/**
 * \page themes Themes
 *
 * Each extension has a theme with a specific name -- eg. the extension Setup
 * which is stored in ext/setup/main.php will have a theme called SetupTheme
 * stored in ext/setup/theme.php. If you want to customise it, create a class
 * in the file themes/mytheme/setup.theme.php called CustomSetupTheme which
 * extends SetupTheme and overrides some of its methods.
 *
 * Generally an extension should only deal with processing data; whenever it
 * wants to display something, it should pass the data to be displayed to the
 * theme object, and the theme will add the data into the global $page
 * structure.
 *
 * A page should make sure that all the data it outputs is free from dangerous
 * data by using html_escape(), url_escape(), or int_escape() as appropriate.
 *
 * Because some HTML can be placed anywhere according to the theme, coming up
 * with the correct way to link to a page can be hard -- thus we have the
 * make_link() function, which will take a path like "post/list" and turn it
 * into a full and correct link, eg /myboard/post/list, /foo/index.php?q=post/list,
 * etc depending on how things are set up. This should always be used to link
 * to pages rather than hardcoding a path.
 *
 * Various other common functions are available as part of the Themelet class.
 */

abstract class PageMode
{
    const REDIRECT = 'redirect';
    const DATA = 'data';
    const PAGE = 'page';
    const FILE = 'file';
}

/**
 * Class Page
 *
 * A data structure for holding all the bits of data that make up a page.
 *
 * The various extensions all add whatever they want to this structure,
 * then Layout turns it into HTML.
 */
class Page
{
    /** @var string */
    public $mode = PageMode::PAGE;
    /** @var string */
    public $type = "text/html; charset=utf-8";

    /**
     * Set what this page should do; "page", "data", or "redirect".
     */
    public function set_mode(string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Set the page's MIME type.
     */
    public function set_type(string $type): void
    {
        $this->type = $type;
    }

    public function __construct()
    {
        if (@$_GET["flash"]) {
            $this->flash[] = $_GET['flash'];
            unset($_GET["flash"]);
        }
    }

    // ==============================================

    /** @var string; public only for unit test */
    public $data = "";

    /** @var string; */
    public $file = null;

    /** @var string; public only for unit test */
    public $filename = null;

    private $disposition = null;

    /**
     * Set the raw data to be sent.
     */
    public function set_data(string $data): void
    {
        $this->data = $data;
    }

    public function set_file(string $file): void
    {
        $this->file = $file;
    }

    /**
     * Set the recommended download filename.
     */
    public function set_filename(string $filename, string $disposition = "attachment"): void
    {
        $this->filename = $filename;
        $this->disposition = $disposition;
    }

    // ==============================================

    /** @var string */
    private $redirect = "";

    /**
     * Set the URL to redirect to (remember to use make_link() if linking
     * to a page in the same site).
     */
    public function set_redirect(string $redirect): void
    {
        $this->redirect = $redirect;
    }

    // ==============================================

    /** @var int */
    public $code = 200;

    /** @var string */
    public $title = "";

    /** @var string */
    public $heading = "";

    /** @var string */
    public $subheading = "";

    /** @var string */
    public $quicknav = "";

    /** @var string[] */
    public $html_headers = [];

    /** @var string[] */
    public $http_headers = [];

    /** @var string[][] */
    public $cookies = [];

    /** @var Block[] */
    public $blocks = [];

    /** @var string[] */
    public $flash = [];

    /**
     * Set the HTTP status code
     */
    public function set_code(int $code): void
    {
        $this->code = $code;
    }

    public function set_title(string $title): void
    {
        $this->title = $title;
    }

    public function set_heading(string $heading): void
    {
        $this->heading = $heading;
    }

    public function set_subheading(string $subheading): void
    {
        $this->subheading = $subheading;
    }

    public function flash(string $message): void
    {
        $this->flash[] = $message;
    }

    /**
     * Add a line to the HTML head section.
     */
    public function add_html_header(string $line, int $position = 50): void
    {
        while (isset($this->html_headers[$position])) {
            $position++;
        }
        $this->html_headers[$position] = $line;
    }

    /**
     * Add a http header to be sent to the client.
     */
    public function add_http_header(string $line, int $position = 50): void
    {
        while (isset($this->http_headers[$position])) {
            $position++;
        }
        $this->http_headers[$position] = $line;
    }

    /**
     * The counterpart for get_cookie, this works like php's
     * setcookie method, but prepends the site-wide cookie prefix to
     * the $name argument before doing anything.
     */
    public function add_cookie(string $name, string $value, int $time, string $path): void
    {
        $full_name = COOKIE_PREFIX . "_" . $name;
        $this->cookies[] = [$full_name, $value, $time, $path];
    }

    public function get_cookie(string $name): ?string
    {
        $full_name = COOKIE_PREFIX . "_" . $name;
        if (isset($_COOKIE[$full_name])) {
            return $_COOKIE[$full_name];
        } else {
            return null;
        }
    }

    /**
     * Get all the HTML headers that are currently set and return as a string.
     */
    public function get_all_html_headers(): string
    {
        $data = '';
        ksort($this->html_headers);
        foreach ($this->html_headers as $line) {
            $data .= "\t\t" . $line . "\n";
        }
        return $data;
    }

    /**
     * Removes all currently set HTML headers (Be careful..).
     */
    public function delete_all_html_headers(): void
    {
        $this->html_headers = [];
    }

    /**
     * Add a Block of data to the page.
     */
    public function add_block(Block $block): void
    {
        $this->blocks[] = $block;
    }

    // ==============================================

    /**
     * Display the page according to the mode and data given.
     */
    public function display(): void
    {
        global $page, $user;

        header("HTTP/1.0 {$this->code} Shimmie");
        header("Content-type: " . $this->type);
        header("X-Powered-By: SCore-" . SCORE_VERSION);

        if (!headers_sent()) {
            foreach ($this->http_headers as $head) {
                header($head);
            }
            foreach ($this->cookies as $c) {
                setcookie($c[0], $c[1], $c[2], $c[3]);
            }
        } else {
            print "Error: Headers have already been sent to the client.";
        }

        switch ($this->mode) {
            case PageMode::PAGE:
                if (CACHE_HTTP) {
                    header("Vary: Cookie, Accept-Encoding");
                    if ($user->is_anonymous() && $_SERVER["REQUEST_METHOD"] == "GET") {
                        header("Cache-control: public, max-age=600");
                        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 600) . ' GMT');
                    } else {
                        #header("Cache-control: private, max-age=0");
                        header("Cache-control: no-cache");
                        header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 600) . ' GMT');
                    }
                }
                #else {
                #	header("Cache-control: no-cache");
                #	header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 600) . ' GMT');
                #}
                usort($this->blocks, "blockcmp");
                $pnbe = new PageNavBuildingEvent();
                send_event($pnbe);

                $nav_links = $pnbe->links;

                $active_link = null;
                // To save on event calls, we check if one of the top-level links has already been marked as active
                foreach ($nav_links as $link) {
                    if ($link->active===true) {
                        $active_link = $link;
                        break;
                    }
                }
                $sub_links = null;
                // If one is, we just query for sub-menu options under that one tab
                if ($active_link!==null) {
                    $psnbe = new PageSubNavBuildingEvent($active_link->name);
                    send_event($psnbe);
                    $sub_links = $psnbe->links;
                } else {
                    // Otherwise we query for the sub-items under each of the tabs
                    foreach ($nav_links as $link) {
                        $psnbe = new PageSubNavBuildingEvent($link->name);
                        send_event($psnbe);

                        // Now we check for a current link so we can identify the sub-links to show
                        foreach ($psnbe->links as $sub_link) {
                            if ($sub_link->active===true) {
                                $sub_links = $psnbe->links;
                                break;
                            }
                        }
                        // If the active link has been detected, we break out
                        if ($sub_links!==null) {
                            $link->active = true;
                            break;
                        }
                    }
                }



                $sub_links = $sub_links??[];
                usort($nav_links, "sort_nav_links");
                usort($sub_links, "sort_nav_links");

                $this->add_auto_html_headers();
                $layout = new Layout();
                $layout->display_page($page, $nav_links, $sub_links);
                break;
            case PageMode::DATA:
                header("Content-Length: " . strlen($this->data));
                if (!is_null($this->filename)) {
                    header('Content-Disposition: ' . $this->disposition . '; filename=' . $this->filename);
                }
                print $this->data;
                break;
            case PageMode::FILE:
                if (!is_null($this->filename)) {
                    header('Content-Disposition: ' . $this->disposition . '; filename=' . $this->filename);
                }

                //https://gist.github.com/codler/3906826

                $size = filesize($this->file); // File size
                $length = $size;           // Content length
                $start = 0;               // Start byte
                $end = $size - 1;       // End byte

                header("Content-Length: " . strlen($size));
                header('Accept-Ranges: bytes');

                if (isset($_SERVER['HTTP_RANGE'])) {
                    $c_start = $start;
                    $c_end = $end;
                    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                    if (strpos($range, ',') !== false) {
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');
                        header("Content-Range: bytes $start-$end/$size");
                        break;
                    }
                    if ($range == '-') {
                        $c_start = $size - substr($range, 1);
                    } else {
                        $range = explode('-', $range);
                        $c_start = $range[0];
                        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
                    }
                    $c_end = ($c_end > $end) ? $end : $c_end;
                    if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');
                        header("Content-Range: bytes $start-$end/$size");
                        break;
                    }
                    $start = $c_start;
                    $end = $c_end;
                    $length = $end - $start + 1;
                    header('HTTP/1.1 206 Partial Content');
                }
                header("Content-Range: bytes $start-$end/$size");
                header("Content-Length: " . $length);


                $fp = fopen($this->file, 'r');
                try {
                    fseek($fp, $start);
                    $buffer = 1024 * 64;
                    while (!feof($fp) && ($p = ftell($fp)) <= $end) {
                        if ($p + $buffer > $end) {
                            $buffer = $end - $p + 1;
                        }
                        set_time_limit(0);
                        echo fread($fp, $buffer);
                        flush();

                        // After flush, we can tell if the client browser has disconnected.
                        // This means we can start sending a large file, and if we detect they disappeared
                        // then we can just stop and not waste any more resources or bandwidth.
                        if (connection_status() != 0) {
                            break;
                        }
                    }
                } finally {
                    fclose($fp);
                }
                break;
            case PageMode::REDIRECT:
                if ($this->flash) {
                    $this->redirect .= (strpos($this->redirect, "?") === false) ? "?" : "&";
                    $this->redirect .= "flash=" . url_escape(implode("\n", $this->flash));
                }
                header('Location: ' . $this->redirect);
                print 'You should be redirected to <a href="' . $this->redirect . '">' . $this->redirect . '</a>';
                break;
            default:
                print "Invalid page mode";
                break;
        }
    }

    /**
     * This function grabs all the CSS and JavaScript files sprinkled throughout Shimmie's folders,
     * concatenates them together into two large files (one for CSS and one for JS) and then stores
     * them in the /cache/ directory for serving to the user.
     *
     * Why do this? Two reasons:
     *  1. Reduces the number of files the user's browser needs to download.
     *  2. Allows these cached files to be compressed/minified by the admin.
     *
     * TODO: This should really be configurable somehow...
     */
    public function add_auto_html_headers(): void
    {
        global $config;

        $data_href = get_base_href();
        $theme_name = $config->get_string(SetupConfig::THEME, 'default');

        $this->add_html_header("<script type='text/javascript'>base_href = '$data_href';</script>", 40);

        # static handler will map these to themes/foo/static/bar.ico or ext/handle_static/static/bar.ico
        $this->add_html_header("<link rel='icon' type='image/x-icon' href='$data_href/favicon.ico'>", 41);
        $this->add_html_header("<link rel='apple-touch-icon' href='$data_href/apple-touch-icon.png'>", 42);

        //We use $config_latest to make sure cache is reset if config is ever updated.
        $config_latest = 0;
        foreach (zglob("data/config/*") as $conf) {
            $config_latest = max($config_latest, filemtime($conf));
        }

        /*** Generate CSS cache files ***/
        $css_latest = $config_latest;
        $css_files = array_merge(
            zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/style.css"),
            zglob("themes/$theme_name/style.css")
        );
        foreach ($css_files as $css) {
            $css_latest = max($css_latest, filemtime($css));
        }
        $css_md5 = md5(serialize($css_files));
        $css_cache_file = data_path("cache/style/{$theme_name}.{$css_latest}.{$css_md5}.css");
        if (!file_exists($css_cache_file)) {
            $css_data = "";
            foreach ($css_files as $file) {
                $file_data = file_get_contents($file);
                $pattern = '/url[\s]*\([\s]*["\']?([^"\'\)]+)["\']?[\s]*\)/';
                $replace = 'url("../../../' . dirname($file) . '/$1")';
                $file_data = preg_replace($pattern, $replace, $file_data);
                $css_data .= $file_data . "\n";
            }
            file_put_contents($css_cache_file, $css_data);
        }
        $this->add_html_header("<link rel='stylesheet' href='$data_href/$css_cache_file' type='text/css'>", 43);

        /*** Generate JS cache files ***/
        $js_latest = $config_latest;
        $js_files = array_merge(
            [
                "vendor/bower-asset/jquery/dist/jquery.min.js",
                "vendor/bower-asset/jquery-timeago/jquery.timeago.js",
                "vendor/bower-asset/tablesorter/jquery.tablesorter.min.js",
                "vendor/bower-asset/js-cookie/src/js.cookie.js",
                "ext/handle_static/modernizr-3.3.1.custom.js",
            ],
            zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/script.js"),
            zglob("themes/$theme_name/script.js")
        );
        foreach ($js_files as $js) {
            $js_latest = max($js_latest, filemtime($js));
        }
        $js_md5 = md5(serialize($js_files));
        $js_cache_file = data_path("cache/script/{$theme_name}.{$js_latest}.{$js_md5}.js");
        if (!file_exists($js_cache_file)) {
            $js_data = "";
            foreach ($js_files as $file) {
                $js_data .= file_get_contents($file) . "\n";
            }
            file_put_contents($js_cache_file, $js_data);
        }
        $this->add_html_header("<script defer src='$data_href/$js_cache_file' type='text/javascript'></script>", 44);
    }
}

class PageNavBuildingEvent extends Event
{
    public $links = [];

    public function add_nav_link(string $name, Link $link, string $desc, ?bool $active = null, int $order = 50)
    {
        $this->links[]  = new NavLink($name, $link, $desc, $active, $order);
    }
}

class PageSubNavBuildingEvent extends Event
{
    public $parent;

    public $links = [];

    public function __construct(string $parent)
    {
        parent::__construct();
        $this->parent= $parent;
    }

    public function add_nav_link(string $name, Link $link, string $desc, ?bool $active = null, int $order = 50)
    {
        $this->links[]  = new NavLink($name, $link, $desc, $active, $order);
    }
}

class NavLink
{
    public $name;
    public $link;
    public $description;
    public $order;
    public $active = false;

    public function __construct(String $name, Link $link, String $description, ?bool $active = null, int $order = 50)
    {
        global $config;

        $this->name = $name;
        $this->link = $link;
        $this->description = $description;
        $this->order = $order;
        if ($active==null) {
            $query = ltrim(_get_query(), "/");
            if ($query === "") {
                // This indicates the front page, so we check what's set as the front page
                $front_page = trim($config->get_string(SetupConfig::FRONT_PAGE), "/");

                if ($front_page === $link->page) {
                    $this->active = true;
                } else {
                    $this->active = self::is_active([$link->page], $front_page);
                }
            } elseif ($query===$link->page) {
                $this->active = true;
            } else {
                $this->active = self::is_active([$link->page]);
            }
        } else {
            $this->active = $active;
        }
    }

    public static function is_active(array $pages_matched, string $url = null): bool
    {
        /**
         * Woo! We can actually SEE THE CURRENT PAGE!! (well... see it highlighted in the menu.)
         */
        $url = $url??ltrim(_get_query(), "/");

        $re1='.*?';
        $re2='((?:[a-z][a-z_]+))';

        if (preg_match_all("/".$re1.$re2."/is", $url, $matches)) {
            $url=$matches[1][0];
        }

        $count_pages_matched = count($pages_matched);

        for ($i=0; $i < $count_pages_matched; $i++) {
            if ($url == $pages_matched[$i]) {
                return true;
            }
        }

        return false;
    }
}

function sort_nav_links(NavLink $a, NavLink $b)
{
    return $a->order - $b->order;
}
