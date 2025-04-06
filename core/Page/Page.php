<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, ARTICLE, B, BODY, BR, DIV, FOOTER, H1, H3, HEAD, HEADER as HTML_HEADER, HTML, LINK, NAV, SCRIPT, SECTION, TITLE, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

/**
 * A data structure for holding all the bits of data that make up a page.
 *
 * The various extensions all add whatever they want to this structure,
 * then Layout turns it into HTML.
 */
class Page
{
    public PageMode $mode = PageMode::PAGE;
    private MimeType $mime;

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
    protected function set_mime(MimeType|string $mime): void
    {
        if (is_string($mime)) {
            $mime = new MimeType($mime);
        }
        $this->mime = $mime;
    }

    public function __construct()
    {
        $this->mime = new MimeType(MimeType::HTML . "; " . MimeType::CHARSET_UTF8);
        if (@$_GET["flash"]) {
            $this->flash[] = $_GET['flash'];
            unset($_GET["flash"]);
        }
    }

    // ==============================================

    public string $data = "";  // public only for unit test
    private ?Path $file = null;
    private bool $file_delete = false;
    private ?string $filename = null;
    private ?string $disposition = null;

    /**
     * Set the raw data to be sent.
     */
    public function set_data(MimeType|string $mime, string $data): void
    {
        $this->mode = PageMode::DATA;
        $this->set_mime($mime);
        $this->data = $data;
    }

    public function set_file(MimeType|string $mime, Path $file, bool $delete = false): void
    {
        $this->mode = PageMode::FILE;
        $this->set_mime($mime);
        $this->file = $file;
        $this->file_delete = $delete;
    }

    /**
     * Set the recommended download filename.
     */
    public function set_filename(string $filename, string $disposition = "attachment"): void
    {
        $max_len = 250;
        if (strlen($filename) > $max_len) {
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
    public function set_redirect(Url $redirect): void
    {
        $this->mode = PageMode::REDIRECT;
        $this->redirect = (string)$redirect;
    }

    // ==============================================

    public int $code = 200;
    public string $title = "";
    public string $heading = "";
    public string $subheading = "";
    protected string $layout = "grid";

    /** @var HTMLElement[] */
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
        if ($this->heading === "") {
            $this->heading = $title;
        }
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

    public function set_layout(string $layout): void
    {
        $this->layout = $layout;
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
        $full_name = SysConfig::getCookiePrefix() . "_" . $name;
        $this->cookies[] = new Cookie($full_name, $value, $time, $path);
    }

    public function get_cookie(string $name): ?string
    {
        $full_name = SysConfig::getCookiePrefix() . "_" . $name;
        if (isset($_COOKIE[$full_name])) {
            return $_COOKIE[$full_name];
        } else {
            return null;
        }
    }

    /**
     * Add a line to the HTML head section.
     */
    public function add_html_header(HTMLElement $line, int $position = 50): void
    {
        while (isset($this->html_headers[$position])) {
            $position++;
        }
        $this->html_headers[$position] = $line;
    }

    /**
     * Get all the HTML headers that are currently set.
     * @return HTMLElement[]
     */
    public function get_all_html_headers(): array
    {
        ksort($this->html_headers);
        return $this->html_headers;
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
    public function find_block(?string $text): Block
    {
        foreach ($this->blocks as $block) {
            if ($block->header === $text) {
                return $block;
            }
        }
        throw new \Exception("Block not found: $text");
    }

    // ==============================================

    public function send_headers(): void
    {
        if (!headers_sent()) {
            header("HTTP/1.1 {$this->code} Shimmie");
            header("Content-type: " . $this->mime);
            header("X-Powered-By: Shimmie-" . SysConfig::getVersion());

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
        Ctx::$tracer->begin("Display ({$this->mode->name})");
        match($this->mode) {
            PageMode::MANUAL => null,
            PageMode::PAGE => $this->display_page(),
            PageMode::DATA => $this->display_data(),
            PageMode::FILE => $this->display_file(),
            PageMode::REDIRECT => $this->display_redirect(),
        };
        Ctx::$tracer->end();
    }

    private function display_page(): void
    {
        $this->send_headers();
        usort($this->blocks, Block::cmp(...));
        $this->add_auto_html_headers();
        $this->render();
    }

    private function display_data(): void
    {
        $this->send_headers();
        header("Content-Length: " . strlen($this->data));
        if (!is_null($this->filename)) {
            header('Content-Disposition: ' . $this->disposition . '; filename=' . $this->filename);
        }
        print $this->data;
    }

    private function display_file(): void
    {
        $this->send_headers();
        if (!is_null($this->filename)) {
            header('Content-Disposition: ' . $this->disposition . '; filename=' . $this->filename);
        }
        assert(!is_null($this->file), "file should not be null with PageMode::FILE");

        // https://gist.github.com/codler/3906826
        $size = $this->file->filesize(); // File size
        $length = $size;           // Content length
        $start = 0;               // Start byte
        $end = $size - 1;       // End byte

        header("Content-Length: " . $size);
        header('Accept-Ranges: bytes');

        if (isset($_SERVER['HTTP_RANGE']) && is_string($_SERVER['HTTP_RANGE'])) {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (str_contains($range, ',')) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                return;
            }
            if ($range === '-') {
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
                return;
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;
            header('HTTP/1.1 206 Partial Content');
        }
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);

        try {
            Filesystem::stream_file($this->file, $start, $end);
        } finally {
            if ($this->file_delete === true) {
                $this->file->unlink();
            }
        }
    }

    private function display_redirect(): void
    {
        $this->send_headers();
        if ($this->flash) {
            $this->redirect = (string)Url::parse($this->redirect)->withModifiedQuery(["flash" => implode("\n", $this->flash)]);
        }
        header('Location: ' . $this->redirect);
        print 'You should be redirected to <a href="' . $this->redirect . '">' . $this->redirect . '</a>';
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
        $data_href = (string)Url::base();
        $theme_name = get_theme();

        # static handler will map these to themes/foo/static/bar.ico or ext/static_files/static/bar.ico
        $this->add_html_header(LINK([
            'rel' => 'icon',
            'type' => 'image/x-icon',
            'href' => "$data_href/favicon.ico"
        ]), 41);
        $this->add_html_header(LINK([
            'rel' => 'apple-touch-icon',
            'href' => "$data_href/apple-touch-icon.png"
        ]), 42);

        //We use $config_latest to make sure cache is reset if config is ever updated.
        $config_latest = 0;
        foreach (Filesystem::zglob("data/config/*") as $conf) {
            $config_latest = max($config_latest, $conf->filemtime());
        }

        $css_cache_file = $this->get_css_cache_file($theme_name, $config_latest);
        $this->add_html_header(LINK([
            'rel' => 'stylesheet',
            'href' => "$data_href/{$css_cache_file->str()}",
            'type' => 'text/css'
        ]), 43);

        $initjs_cache_file = $this->get_initjs_cache_file($theme_name, $config_latest);
        $this->add_html_header(SCRIPT([
            'src' => "$data_href/{$initjs_cache_file->str()}",
            'type' => 'text/javascript'
        ]));

        $js_cache_file = $this->get_js_cache_file($theme_name, $config_latest);
        $this->add_html_header(SCRIPT([
            'defer' => true,
            'src' => "$data_href/{$js_cache_file->str()}",
            'type' => 'text/javascript'
        ]));
    }

    /**
     * @param Path[] $files
     */
    private function get_cache_file(string $type, string $ext, string $theme_name, int $timestamp, array $files): Path
    {
        foreach ($files as $file) {
            $timestamp = max($timestamp, $file->filemtime());
        }
        $md5 = md5(serialize($files));
        $cache_file = Filesystem::data_path("cache/{$type}/{$theme_name}.{$timestamp}.{$md5}.{$ext}");
        if (!$cache_file->exists()) {
            $mcss = new \MicroBundler\MicroBundler();
            foreach ($files as $file) {
                $mcss->addSource($file->str());
            }
            $mcss->save($cache_file->str());
        }
        return $cache_file;
    }

    private function get_css_cache_file(string $theme_name, int $config_latest): Path
    {
        $files = array_merge(
            Filesystem::zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/style.css"),
            Filesystem::zglob("themes/$theme_name/{" . implode(",", $this->get_theme_stylesheets()) . "}")
        );
        return self::get_cache_file('style', 'css', $theme_name, $config_latest, $files);
    }

    private function get_initjs_cache_file(string $theme_name, int $config_latest): Path
    {
        $files = array_merge(
            Filesystem::zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/init.js"),
            Filesystem::zglob("themes/$theme_name/init.js")
        );
        return self::get_cache_file('initscript', 'js', $theme_name, $config_latest, $files);
    }

    private function get_js_cache_file(string $theme_name, int $config_latest): Path
    {
        $files = array_merge(
            [
                new Path("vendor/bower-asset/jquery/dist/jquery.min.js"),
                new Path("vendor/bower-asset/jquery-timeago/jquery.timeago.js"),
                new Path("vendor/bower-asset/js-cookie/src/js.cookie.js"),
            ],
            Filesystem::zglob("ext/{" . Extension::get_enabled_extensions_as_string() . "}/script.js"),
            Filesystem::zglob("themes/$theme_name/{" . implode(",", $this->get_theme_scripts()) . "}")
        );
        return self::get_cache_file('script', 'js', $theme_name, $config_latest, $files);
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
        if ($active_link !== null && $active_link->category !== null) {
            $psnbe = send_event(new PageSubNavBuildingEvent($active_link->category));
            $sub_links = $psnbe->links;
        } else {
            // Otherwise we query for the sub-items under each of the tabs
            foreach ($nav_links as $link) {
                if ($link->category === null) {
                    continue;
                }
                $psnbe = send_event(new PageSubNavBuildingEvent($link->category));

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
        $struct = $this->html_html(
            $this->head_html(),
            $this->body_html()
        );
        print (string)$struct;
    }

    public function html_html(HTMLElement $head, HTMLElement $body): HTMLElement
    {
        return emptyHTML(
            \MicroHTML\rawHTML("<!doctype html>"),
            HTML(
                ["lang" => "en"],
                $head,
                $body,
            )
        );
    }

    protected function head_html(): HTMLElement
    {
        return HEAD(
            TITLE($this->title),
            ...$this->get_all_html_headers(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function body_attrs(): array
    {
        return [
            "class" => "layout-{$this->layout}",
            "data-userclass" => Ctx::$user->class->name,
            "data-base-href" => (string)Url::base(),
            "data-base-link" => (string)make_link(""),
        ];
    }

    protected function body_html(): HTMLElement
    {
        $left_block_html = [];
        $main_block_html = [];
        $sub_block_html = [];

        foreach ($this->blocks as $block) {
            switch ($block->section) {
                case "left":
                    $left_block_html[] = $this->block_html($block, true);
                    break;
                case "main":
                    $main_block_html[] = $this->block_html($block, false);
                    break;
                case "subheading":
                    $sub_block_html[] = $this->block_html($block, false);
                    break;
                default:
                    print "<p>error: {$block->header} using an unknown section ({$block->section})";
                    break;
            }
        }

        $footer_html = $this->footer_html();
        $flash_html = $this->flash_html();
        return BODY(
            $this->body_attrs(),
            HTML_HEADER(
                H1($this->heading),
                ...$sub_block_html
            ),
            NAV(
                ...$left_block_html
            ),
            ARTICLE(
                $flash_html,
                ...$main_block_html
            ),
            FOOTER(
                $footer_html
            )
        );
    }

    protected function block_html(Block $block, bool $hidable): HTMLElement
    {
        $html = SECTION(['id' => $block->id]);
        if (!empty($block->header)) {
            $html->appendChild(H3(["data-toggle-sel" => "#{$block->id}", "class" => $hidable ? "shm-toggler" : ""], $block->header));
        }
        if (!empty($block->body)) {
            $html->appendChild(DIV(['class' => "blockbody"], $block->body));
        }
        return $html;
    }

    protected function flash_html(): HTMLElement
    {
        if ($this->flash) {
            return DIV(["id" => "flash"], B(["class" => "blink"], joinHTML(BR(), $this->flash)));
        }
        return emptyHTML();
    }

    protected function footer_html(): HTMLElement
    {
        $debug = get_debug_info();
        $contact_link = contact_link();
        return joinHTML("", [
            "Media © their respective owners, ",
            A(["href" => "https://code.shishnet.org/shimmie2/", "title" => $debug], "Shimmie"),
            " © ",
            A(["href" => "https://www.shishnet.org/"], "Shish"),
            " & ",
            A(["href" => "https://github.com/shish/shimmie2/graphs/contributors"], "The Team"),
            " 2007-2025, based on the Danbooru concept.",
            $contact_link ? emptyHTML(BR(), A(["href" => $contact_link], "Contact")) : ""
        ]);
    }
}
