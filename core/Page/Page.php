<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A data structure for holding all the bits of data that make up a response
 * (This class probably should've been called Response but it's too late now).
 *
 * Note that there are several kinds of response (Page, Data, Error, etc) and
 * the code for handling them is in the respective traits.
 */
class Page
{
    use Page_Page;
    use Page_Error;
    use Page_Data;
    use Page_File;
    use Page_Redirect;
    use Page_Error;

    public int $code = 200;
    public PageMode $mode = PageMode::PAGE;
    private MimeType $mime;

    /** @var string[] */
    private array $http_headers = [];

    /** @var Cookie[] */
    private array $cookies = [];

    public function __construct()
    {
        $this->mime = new MimeType(MimeType::HTML . "; charset=utf-8");
        if (@$_GET["flash"]) {
            $this->flash[] = $_GET['flash'];
            unset($_GET["flash"]);
        }
    }

    /**
     * Set what this page should do; "page", "data", or "redirect".
     */
    public function set_mode(PageMode $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * Set the HTTP status code
     */
    public function set_code(int $code): void
    {
        $this->code = $code;
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
    public function add_cookie(string $name, string $value, int $time): void
    {
        $path = ((string)Url::base()) . "/";
        $this->cookies[] = new Cookie("shm_$name", $value, $time, $path);
    }

    public function get_cookie(string $name): ?string
    {
        return $_COOKIE["shm_$name"] ?? null;
    }

    public function send_headers(): void
    {
        if (!headers_sent()) {
            header("HTTP/1.1 {$this->code} Shimmie");
            header("Content-type: " . $this->mime);
            header("X-Powered-By: Shimmie-" . SysConfig::getVersion());
            $dbtime = round(Ctx::$database->dbtime * 1000, 2);
            $totaltime = round((ftime() - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000, 2);
            $apptime = round($totaltime - $dbtime, 2);
            header("Server-Timing: db;dur={$dbtime}, app;dur={$apptime}, total;dur={$totaltime}");

            foreach ($this->http_headers as $head) {
                header($head);
            }
            foreach ($this->cookies as $c) {
                setcookie($c->name, $c->value, $c->time, $c->path);
            }
        } else {
            throw new ServerError("Headers have already been sent to the client.");
        }
    }

    /**
     * Display the page according to the mode and data given.
     */
    public function display(): void
    {
        $span = Ctx::$tracer->startSpan("Display ({$this->mode->name})");
        match($this->mode) {
            PageMode::MANUAL => null,
            PageMode::PAGE => $this->display_page(),
            PageMode::ERROR => $this->display_error(),
            PageMode::DATA => $this->display_data(),
            PageMode::FILE => $this->display_file(),
            PageMode::REDIRECT => $this->display_redirect(),
        };
        $span->end();
    }
}
