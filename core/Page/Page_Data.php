<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * All of the stuff related to data-type responses
 */
trait Page_Data
{
    use Page_Common;

    public private(set) string $data = "";
    public private(set) ?string $data_filename = null;
    public private(set) ?string $data_disposition = null;

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
        $disposition = $this->gen_disposition_header($this->data_disposition, $this->data_filename);
        $this->add_http_header("Content-Disposition: $disposition");
        $this->add_http_header("Content-Length: " . strlen($this->data));
        $this->send_headers();
        print $this->data;
    }
}
