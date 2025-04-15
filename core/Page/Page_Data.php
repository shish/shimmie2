<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * All of the stuff related to data-type responses
 */
trait Page_Data
{
    abstract public function set_mode(PageMode $mode): void;
    abstract public function set_mime(MimeType|string $mime): void;
    abstract public function send_headers(): void;

    public string $data = "";  // public only for unit test
    private ?string $data_filename = null;
    private ?string $data_disposition = null;

    /**
     * Set the raw data to be sent.
     */
    public function set_data(
        MimeType|string $mime,
        string $data,
        ?string $filename = null,
        ?string $disposition = null,
    ): void {
        $this->set_mode(PageMode::DATA);
        $this->set_mime($mime);
        $this->data = $data;
        $this->data_filename = truncate_filename($filename);
        $this->data_disposition = $disposition;
    }

    protected function display_data(): void
    {
        $this->send_headers();
        if (!is_null($this->data_filename)) {
            header('Content-Disposition: ' . $this->data_disposition . '; filename=' . $this->data_filename);
        }
        header("Content-Length: " . strlen($this->data));
        print $this->data;
    }
}
