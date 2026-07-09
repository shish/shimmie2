<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * All of the stuff related to file-type responses
 */
trait Page_File
{
    abstract public function set_mode(PageMode $mode): void;
    abstract public function set_mime(MimeType|string $mime): void;
    abstract public function set_code(int $code): void;
    abstract public function add_http_header(string $line, int $position = 50): void;
    abstract public function send_headers(): void;

    public private(set) ?Path $file = null;
    public private(set) bool $file_delete = false;
    public private(set) ?string $file_filename = null;
    public private(set) ?string $file_disposition = null;

    public function set_file(
        MimeType|string $mime,
        Path $file,
        bool $delete = false,
        ?string $filename = null,
        ?string $disposition = null,
    ): void {
        $this->set_mode(PageMode::FILE);
        $this->set_mime($mime);
        $this->file = $file;
        $this->file_delete = $delete;
        $this->file_filename = truncate_filename($filename);
        $this->file_disposition = $disposition;
    }

    protected function display_file(): void
    {
        $file = $this->file;
        assert(!is_null($file), "file should not be null with PageMode::FILE");

        // Check If-Modified-Since and return early if appropriate
        if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
            $if_modified_since = \Safe\preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
        } else {
            $if_modified_since = "";
        }
        $gmdate_mod = gmdate('D, d M Y H:i:s', $file->filemtime()) . ' GMT';
        if ($if_modified_since === $gmdate_mod) {
            $this->set_code(304);
            $this->send_headers();
            return;
        } else {
            $this->add_http_header("Last-Modified: $gmdate_mod");
        }

        // Set filename
        if (!is_null($this->file_filename)) {
            $this->add_http_header('Content-Disposition: ' . $this->file_disposition . '; filename=' . $this->file_filename);
        }

        // Deal with Range header
        // https://gist.github.com/codler/3906826
        $size = $file->filesize(); // File size
        $length = $size;           // Content length
        $start = 0;               // Start byte
        $end = $size - 1;       // End byte
        if (isset($_SERVER['HTTP_RANGE']) && is_string($_SERVER['HTTP_RANGE'])) {
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (str_contains($range, ',')) {
                $this->set_code(416);  // Invalid range
                $this->add_http_header("Content-Range: bytes $start-$end/$size");
                $this->send_headers();
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
                $this->set_code(416);  // Invalid range
                $this->add_http_header("Content-Range: bytes $start-$end/$size");
                $this->send_headers();
                return;
            }
            $start = $c_start;
            $end = $c_end;
            $length = $end - $start + 1;
            $this->set_code(206);  // Partial content
        }
        $this->add_http_header('Accept-Ranges: bytes');
        $this->add_http_header("Content-Range: bytes $start-$end/$size");
        $this->add_http_header("Content-Length: " . $length);
        $this->send_headers();

        try {
            Filesystem::stream_file($file, $start, $end);
        } finally {
            if ($this->file_delete === true) {
                $file->unlink();
            }
        }
    }
}
