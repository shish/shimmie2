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
    abstract public function send_headers(): void;

    protected ?Path $file = null;
    protected bool $file_delete = false;
    private ?string $file_filename = null;
    private ?string $file_disposition = null;

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
        $this->send_headers();
        if (!is_null($this->file_filename)) {
            header('Content-Disposition: ' . $this->file_disposition . '; filename=' . $this->file_filename);
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
}
