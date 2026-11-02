<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * The core response state (status code, mode, MIME type, headers, cookies)
 * shared by all of the response-type traits
 */
trait Page_Common
{
    public private(set) int $code = 200;
    public private(set) PageMode $mode = PageMode::PAGE;
    public private(set) MimeType $mime;

    /** @var string[] */
    private array $http_headers = [];

    /** @var Cookie[] */
    private array $cookies = [];

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
     * A separate function from add_http_header to make it easier to test
     */
    public function gen_disposition_header(?string $disposition = null, ?string $filename = null): string
    {
        $disposition = $disposition ?? 'inline';
        if (!is_null($filename)) {
            $quoted_filename = 'filename="' . str_replace(['\\', '"'], ['\\\\', '\\"'], $filename) . '"';
            $rfc5987_filename = 'filename*=UTF-8\'\'' . rawurlencode($filename);
            $disposition .= '; ' . $quoted_filename . '; ' . $rfc5987_filename;
        }
        return $disposition;
    }

    /**
     * The counterpart for get_cookie, this works like php's
     * setcookie method, but prepends the site-wide cookie prefix to
     * the $name argument before doing anything.
     *
     * @param string $name Cookie name (will be prefixed with "shm_")
     * @param string $value Cookie value
     * @param int $time Expiration time (Unix timestamp)
     * @param bool $secure Send only over HTTPS (default: true, auto-detect based on request)
     * @param bool $httponly Prevent JavaScript access (default: true)
     * @param 'Strict'|'Lax'|'None' $samesite SameSite attribute
     */
    public function add_cookie(
        string $name,
        string $value,
        int $time,
        ?bool $secure = null,
        bool $httponly = true,
        string $samesite = 'Lax'
    ): void {
        $path = ((string)Url::base()) . "/";

        // Auto-detect secure flag based on current request if not explicitly set
        if ($secure === null) {
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        }

        $this->cookies[] = new Cookie("shm_$name", $value, $time, $path, $secure, $httponly, $samesite);
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
                /** @var 'Strict'|'Lax'|'None' $samesite */
                $samesite = $c->samesite;
                setcookie(
                    $c->name,
                    $c->value,
                    [
                        'expires' => $c->time,
                        'path' => $c->path,
                        'secure' => $c->secure,
                        'httponly' => $c->httponly,
                        'samesite' => $samesite
                    ]
                );
            }
        } else {
            throw new ServerError("Headers have already been sent to the client.");
        }
    }
}
