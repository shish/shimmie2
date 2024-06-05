<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{emptyHTML,rawHTML,HTML,HEAD,BODY};

require_once "core/event.php";

enum PageMode: string
{
    case REDIRECT = 'redirect';
    case DATA = 'data';
    case PAGE = 'page';
    case FILE = 'file';
    case MANUAL = 'manual';
}

class Cookie
{
    public string $name;
    public string $value;
    public int $time;
    public string $path;

    public function __construct(string $name, string $value, int $time, string $path)
    {
        $this->name = $name;
        $this->value = $value;
        $this->time = $time;
        $this->path = $path;
    }
}

/**
 * Class Page
 *
 * A data structure for holding all the bits of data that make up a page.
 *
 * The various extensions all add whatever they want to this structure,
 * then Layout turns it into HTML.
 */
class BasePage
{
    public PageMode $mode = PageMode::PAGE;
    private string $mime;

    /**
     * Set what this page should do; "page", "data", or "redirect".
     */
    public function set_mode(PageMode $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Set the page's MIME type.
     */
    public function set_mime(string $mime): void
    {
        $this->mime = $mime;
    }

    public function __construct()
    {
        $this->mime = MimeType::add_parameters(MimeType::HTML, MimeType::CHARSET_UTF8);
        if (@$_GET["flash"]) {
            $this->flash[] = $_GET['flash'];
            unset($_GET["flash"]);
        }
    }

    // ==============================================

    public string $data = "";  // public only for unit test
    private ?string $file = null;
    private bool $file_delete = false;
    private ?string $filename = null;
    private ?string $disposition = null;

    /**
     * Set the raw data to be sent.
     */
    public function set_data(string $data): void
    {
        $this->data = $data;
    }

    public function set_file(string $file, bool $delete = false): void
    {
        $this->file = $file;
        $this->file_delete = $delete;
    }

    /**
     * Set the recommended download filename.
     */
    public function set_filename(string $filename, string $disposition = "attachment"): void
    {
        $max_len = 250;
        if(strlen($filename) > $max_len) {
            // remove extension, truncate filename, apply extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = substr($filename, 0, $max_len - strlen($ext) - 1) . '.' . $ext;
        }
        $this->filename = $filename;
        $this->disposition = $disposition;
    }

    // ==============================================

    public string $redirect = "";

    /**
     * Set the URL to redirect to (remember to use make_link() if linking
     * to a page in the same site).
     */
    public function set_redirect(string $redirect): void
    {
        $this->redirect = $redirect;
    }

    // ==============================================

    public int $code = 200;
    public string $title = "";
    public string $heading = "";
    public string $subheading = "";
    public bool $left_enabled = true;

    /** @var string[] */
    public array $html_headers = [];

    /** @var string[] */
    public array $http_headers = [];

    /** @var Cookie[] */
    public array $cookies = [];

    /** @var Block[] */
    public array $blocks = [];

    /** @var string[] */
    public array $flash = [];

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

    public function disable_left(): void
    {
        $this->left_enabled = false;
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
        $this->cookies[] = new Cookie($full_name, $value, $time, $path);
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
     * Add a Block of data to the page.
     */
    public function add_block(Block $block): void
    {
        $this->blocks[] = $block;
    }

    /**
     * Find a block which contains the given text
     * (Useful for unit tests)
     */
    public function find_block(string $text): ?Block
    {
        foreach ($this->blocks as $block) {
            if ($block->header == $text) {
                return $block;
            }
        }
        return null;
    }

    // ==============================================

    public function send_headers(): void
    {
        if (!headers_sent()) {
            header("HTTP/1.1 {$this->code} Shimmie");
            header("Content-type: " . $this->mime);
            header("X-Powered-By: Shimmie-" . VERSION);

            foreach ($this->http_headers as $head) {
                header($head);
            }
            foreach ($this->cookies as $c) {
                setcookie($c->name, $c->value, $c->time, $c->path);
            }
        } else {
            print "Error: Headers have already been sent to the client.";
        }
    }

    /**
     * Display the page according to the mode and data given.
     */
    public function display(): void
    {
        if ($this->mode != PageMode::MANUAL) {
            $this->send_headers();
        }

        switch ($this->mode) {
            case PageMode::MANUAL:
                break;
            case PageMode::PAGE:
                usort($this->blocks, "Shimmie2\blockcmp");
                $this->add_auto_html_headers();
                $this->render();
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
                assert($this->file, "file should not be null with PageMode::FILE");

                // https://gist.github.com/codler/3906826
                $size = \Safe\filesize($this->file); // File size
                $length = $size;           // Content length
                $start = 0;               // Start byte
                $end = $size - 1;       // End byte

                header("Content-Length: " . $size);
                header('Accept-Ranges: bytes');

                if (isset($_SERVER['HTTP_RANGE'])) {
                    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                    if (str_contains($range, ',')) {
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');
                        header("Content-Range: bytes $start-$end/$size");
                        break;
                    }
                    if ($range == '-') {
                        $c_start = $size - (int)substr($range, 1);
                        $c_end = $end;
                    } else {
                        $range = explode('-', $range);
                        $c_start = (int)$range[0];
                        $c_end = (isset($range[1]) && is_numeric($range[1])) ? (int)$range[1] : $size;
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

                try {
                    stream_file($this->file, $start, $end);
                } finally {
                    if ($this->file_delete === true) {
                        unlink($this->file);
                    }
                }
                break;
            case PageMode::REDIRECT:
                if ($this->flash) {
                    $this->redirect .= str_contains($this->redirect, "?") ? "&" : "?";
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
     */
    public function add_auto_html_headers(): void
    {
        global $config;

        $data_href = get_base_href();
        $theme_name = $config->get_string(SetupConfig::THEME, 'default');

        # static handler will map these to themes/foo/static/bar.ico or ext/static_files/static/bar.ico
        $this->add_html_header("<link rel='icon' type='image/x-icon' href='$data_href/favicon.ico'>", 41);
        $this->add_html_header("<link rel='apple-touch-icon' href='$data_href/apple-touch-icon.png'>", 42);

        //We use $config_latest to make sure cache is reset if config is ever updated.
        $config_latest = 0;
        foreach (zglob("data/config/*") as $conf) {
            $config_latest = max($config_latest, filemtime($conf));
        }

        $css_cache_file = $this->get_css_cache_file($theme_name, $config_latest);
        $this->add_html_header("<link rel='stylesheet' href='$data_href/$css_cache_file' type='text/css'>", 43);

        $initjs_cache_file = $this->get_initjs_cache_file($theme_name, $config_latest);
        $this->add_html_header("<script src='$data_href/$initjs_cache_file' type='text/javascript'></script>", 44);

        $js_cache_file = $this->get_js_cache_file($theme_name, $config_latest);
        $this->add_html_header("<script defer src='$data_href/$js_cache_file' type='text/javascript'></script>", 44);
    }

    private function get_css_cache_file(string $theme_name, int $config_latest): string
    {
        $css_latest = $config_latest;
        $css_files = array_merge(
            zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/style.css"),
            zglob("themes/$theme_name/{" . implode(",", $this->get_theme_stylesheets()) . "}")
        );
        foreach ($css_files as $css) {
            $css_latest = max($css_latest, filemtime($css));
        }
        $css_md5 = md5(serialize($css_files));
        $css_cache_file = data_path("cache/style/{$theme_name}.{$css_latest}.{$css_md5}.css");
        if (!file_exists($css_cache_file)) {
            $mcss = new \MicroBundler\MicroBundler();
            foreach($css_files as $css) {
                $mcss->addSource($css);
            }
            $mcss->save($css_cache_file);
        }

        return $css_cache_file;
    }

    private function get_initjs_cache_file(string $theme_name, int $config_latest): string
    {
        $js_latest = $config_latest;
        $js_files = array_merge(
            zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/init.js"),
            zglob("themes/$theme_name/init.js")
        );
        foreach ($js_files as $js) {
            $js_latest = max($js_latest, filemtime($js));
        }
        $js_md5 = md5(serialize($js_files));
        $js_cache_file = data_path("cache/initscript/{$theme_name}.{$js_latest}.{$js_md5}.js");
        if (!file_exists($js_cache_file)) {
            $mcss = new \MicroBundler\MicroBundler();
            foreach($js_files as $js) {
                $mcss->addSource($js);
            }
            $mcss->save($js_cache_file);
        }

        return $js_cache_file;
    }

    private function get_js_cache_file(string $theme_name, int $config_latest): string
    {
        $js_latest = $config_latest;
        $js_files = array_merge(
            [
                "vendor/bower-asset/jquery/dist/jquery.min.js",
                "vendor/bower-asset/jquery-timeago/jquery.timeago.js",
                "vendor/bower-asset/js-cookie/src/js.cookie.js",
            ],
            zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/script.js"),
            zglob("themes/$theme_name/{" . implode(",", $this->get_theme_scripts()) . "}")
        );
        foreach ($js_files as $js) {
            $js_latest = max($js_latest, filemtime($js));
        }
        $js_md5 = md5(serialize($js_files));
        $js_cache_file = data_path("cache/script/{$theme_name}.{$js_latest}.{$js_md5}.js");
        if (!file_exists($js_cache_file)) {
            $mcss = new \MicroBundler\MicroBundler();
            foreach($js_files as $js) {
                $mcss->addSource($js);
            }
            $mcss->save($js_cache_file);
        }

        return $js_cache_file;
    }

    /**
     * @return string[] A list of stylesheets relative to the theme root.
     */
    protected function get_theme_stylesheets(): array
    {
        return ["style.css"];
    }


    /**
     * @return string[] A list of script files relative to the theme root.
     */
    protected function get_theme_scripts(): array
    {
        return ["script.js"];
    }

    /**
     * @return array{0: NavLink[], 1: NavLink[]}
     */
    protected function get_nav_links(): array
    {
        $pnbe = send_event(new PageNavBuildingEvent());

        $nav_links = $pnbe->links;

        $active_link = null;
        // To save on event calls, we check if one of the top-level links has already been marked as active
        foreach ($nav_links as $link) {
            if ($link->active === true) {
                $active_link = $link;
                break;
            }
        }
        $sub_links = null;
        // If one is, we just query for sub-menu options under that one tab
        if ($active_link !== null) {
            $psnbe = send_event(new PageSubNavBuildingEvent($active_link->name));
            $sub_links = $psnbe->links;
        } else {
            // Otherwise we query for the sub-items under each of the tabs
            foreach ($nav_links as $link) {
                $psnbe = send_event(new PageSubNavBuildingEvent($link->name));

                // Now we check for a current link so we can identify the sub-links to show
                foreach ($psnbe->links as $sub_link) {
                    if ($sub_link->active === true) {
                        $sub_links = $psnbe->links;
                        break;
                    }
                }
                // If the active link has been detected, we break out
                if ($sub_links !== null) {
                    $link->active = true;
                    break;
                }
            }
        }

        $sub_links = $sub_links ?? [];

        usort($nav_links, fn (NavLink $a, NavLink $b) => $a->order - $b->order);
        usort($sub_links, fn (NavLink $a, NavLink $b) => $a->order - $b->order);

        return [$nav_links, $sub_links];
    }

    /**
     * turns the Page into HTML
     */
    public function render(): void
    {
        global $config, $user;

        $head = $this->head_html();
        $body = $this->body_html();

        $body_attrs = [
            "data-userclass" => $user->class->name,
            "data-base-href" => get_base_href(),
            "data-base-link" => make_link(""),
        ];

        print emptyHTML(
            rawHTML("<!doctype html>"),
            HTML(
                ["lang" => "en"],
                HEAD(rawHTML($head)),
                BODY($body_attrs, rawHTML($body))
            )
        );
    }

    protected function head_html(): string
    {
        $html_header_html = $this->get_all_html_headers();

        return "
        <title>{$this->title}</title>
        $html_header_html
        ";
    }

    protected function body_html(): string
    {
        $left_block_html = "";
        $main_block_html = "";
        $sub_block_html  = "";

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html .= $block->get_html(true);
                    break;
                case "main":
                    $main_block_html .= $block->get_html(false);
                    break;
                case "subheading":
                    $sub_block_html .= $block->get_html(false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $footer_html = $this->footer_html();
        $flash_html = $this->flash ? "<b id='flash'>".nl2br(html_escape(implode("\n", $this->flash)))."</b>" : "";
        return "
            <header>
                <h1>{$this->heading}</h1>
                $sub_block_html
            </header>
            <nav>
                $left_block_html
            </nav>
            <article>
                $flash_html
                $main_block_html
            </article>
            <footer>
                $footer_html
            </footer>
        ";
    }

    protected function footer_html(): string
    {
        $debug = get_debug_info();
        $contact_link = contact_link();
        $contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";

        return "
			Media &copy; their respective owners,
			<a href=\"https://code.shishnet.org/shimmie2/\">Shimmie</a> &copy;
			<a href=\"https://www.shishnet.org/\">Shish</a> &amp;
			<a href=\"https://github.com/shish/shimmie2/graphs/contributors\">The Team</a>
			2007-2024,
			based on the Danbooru concept.
			$debug
			$contact
        ";
    }
}

class PageNavBuildingEvent extends Event
{
    /** @var NavLink[] */
    public array $links = [];

    public function add_nav_link(string $name, Link $link, string $desc, ?bool $active = null, int $order = 50): void
    {
        $this->links[]  = new NavLink($name, $link, $desc, $active, $order);
    }
}

class PageSubNavBuildingEvent extends Event
{
    public string $parent;

    /** @var NavLink[] */
    public array $links = [];

    public function __construct(string $parent)
    {
        parent::__construct();
        $this->parent = $parent;
    }

    public function add_nav_link(string $name, Link $link, string|HTMLElement $desc, ?bool $active = null, int $order = 50): void
    {
        $this->links[]  = new NavLink($name, $link, $desc, $active, $order);
    }
}

class NavLink
{
    public string $name;
    public Link $link;
    public string|HTMLElement $description;
    public int $order;
    public bool $active = false;

    public function __construct(string $name, Link $link, string|HTMLElement $description, ?bool $active = null, int $order = 50)
    {
        global $config;

        $this->name = $name;
        $this->link = $link;
        $this->description = $description;
        $this->order = $order;
        if ($active == null) {
            $query = _get_query();
            if ($query === "") {
                // This indicates the front page, so we check what's set as the front page
                $front_page = trim($config->get_string(SetupConfig::FRONT_PAGE), "/");

                if ($front_page === $link->page) {
                    $this->active = true;
                } else {
                    $this->active = self::is_active([$link->page], $front_page);
                }
            } elseif ($query === $link->page) {
                $this->active = true;
            } else {
                $this->active = self::is_active([$link->page]);
            }
        } else {
            $this->active = $active;
        }
    }

    /**
     * @param string[] $pages_matched
     */
    public static function is_active(array $pages_matched, string $url = null): bool
    {
        /**
         * Woo! We can actually SEE THE CURRENT PAGE!! (well... see it highlighted in the menu.)
         */
        $url = $url ?? _get_query();

        $re1 = '.*?';
        $re2 = '((?:[a-z][a-z_]+))';

        if (preg_match_all("/".$re1.$re2."/is", $url, $matches)) {
            $url = $matches[1][0];
        }

        $count_pages_matched = count($pages_matched);

        for ($i = 0; $i < $count_pages_matched; $i++) {
            if ($url == $pages_matched[$i]) {
                return true;
            }
        }

        return false;
    }
}
