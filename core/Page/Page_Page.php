<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, ARTICLE, B, BODY, BR, DIV, FOOTER, H1, H3, HEAD, HEADER as HTML_HEADER, HTML, LINK, NAV, SCRIPT, SECTION, TITLE, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

/**
 * All of the stuff related to html-type responses
 */
trait Page_Page
{
    abstract public function set_mode(PageMode $mode): void;

    public string $title = "";
    public string $heading = "";
    public string $subheading = "";
    protected string $layout = "grid";

    protected Navigation $navigation;

    /** @var HTMLElement[] */
    public array $html_headers = [];

    /** @var Block[] */
    public array $blocks = [];

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

    public function set_layout(string $layout): void
    {
        $this->layout = $layout;
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

    public function set_navigation_title(string $title): void
    {
        if (!isset($this->navigation)) {
            $this->navigation = new Navigation();
        }

        $this->navigation->title = $title;
    }

    public function set_navigation(?Url $prev, ?Url $next, ?Url $index = null): void
    {
        if (!isset($this->navigation)) {
            $this->navigation = new Navigation();
        }

        $this->navigation->set_prev($prev);
        $this->navigation->set_index($index);
        $this->navigation->set_next($next);
    }

    public function add_to_navigation(HTMLElement|string $html, int $position = 50): void
    {
        if (!isset($this->navigation)) {
            $this->navigation = new Navigation();
        }

        $this->navigation->add_extra($html, $position);
    }

    protected function build_navigation(): void
    {
        if (!isset($this->navigation)) {
            $this->navigation = new Navigation();
        }

        $user = Ctx::$user;
        if (!$user->is_anonymous()) {
            $this->navigation->add_extra(emptyHTML("Logged in as ", $user->name), 20);
        }

        [$nav_links, $sub_links] = $this->get_nav_links();

        $nav = [];
        foreach ($sub_links as $sublink) {
            $nav[] = $sublink->render();
        }

        if (!empty($nav)) {
            $this->navigation->add_extra(emptyHTML(joinHTML(BR(), $nav), BR()), 19);
        }

        $nav = [];
        foreach ($nav_links as $link) {
            $nav[] = $link->render();
        }

        if (!empty($nav)) {
            $this->navigation->add_extra(joinHTML(BR(), $nav));
        }

        $html = $this->navigation->render();
        $this->add_block(new Block($this->navigation->title, $html, "left", 0));
    }

    protected function display_page(): void
    {
        $this->build_navigation();

        usort($this->blocks, Block::cmp(...));
        $this->add_auto_html_headers();
        $str = (string)$this->render();

        $this->send_headers();
        print $str;
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
            [
                new Path("vendor/bower-asset/js-cookie/src/js.cookie.js"),
            ],
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
    public function get_nav_links(): array
    {
        $pnbe = send_event(new PageNavBuildingEvent());
        $nav_links = $pnbe->links;

        $sub_links = [];
        // To save on event calls, we check if one of the top-level links has already been marked as active
        // If one is, we just query for sub-menu options under that one tab
        if ($pnbe->active_link !== null) {
            $psnbe = send_event(new PageSubNavBuildingEvent($pnbe->active_link->key));
            $sub_links = $psnbe->links;
        } else {
            // Otherwise we query for the sub-items under each of the tabs
            foreach ($nav_links as $link) {
                $psnbe = send_event(new PageSubNavBuildingEvent($link->key));

                // If the active link has been detected, we break out
                if ($psnbe->active_link !== null) {
                    $sub_links = $psnbe->links;
                    $link->active = true;
                    break;
                }
            }
        }

        usort($nav_links, fn (NavLink $a, NavLink $b) => $a->order - $b->order);
        usort($sub_links, fn (NavLink $a, NavLink $b) => $a->order - $b->order);

        return [$nav_links, $sub_links];
    }

    /**
     * turns the Page into HTML
     */
    public function render(): HTMLElement
    {
        return $this->html_html(
            $this->head_html(),
            $this->body_html()
        );
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
            A(["href" => "https://github.com/shish/shimmie2/", "title" => $debug], "Shimmie"),
            " © ",
            A(["href" => "https://www.shishnet.org/"], "Shish"),
            " & ",
            A(["href" => "https://github.com/shish/shimmie2/graphs/contributors"], "The Team"),
            " 2007-2026, based on the Danbooru concept.",
            $contact_link ? emptyHTML(BR(), A(["href" => $contact_link], "Contact")) : ""
        ]);
    }
}
