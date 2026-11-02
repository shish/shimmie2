<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * A data structure for holding all the bits of data that make up a response
 * (This class probably should've been called Response but it's too late now).
 *
 * Note that there are several kinds of response (Page, Data, Error, etc) and
 * the code for handling them is in the respective traits, with the shared
 * core (status code, mime type, headers, cookies) in Page_Common.
 */
class Page
{
    use Page_Common;
    use Page_Page;
    use Page_Error;
    use Page_Data;
    use Page_File;
    use Page_Redirect;

    public function __construct()
    {
        $this->mime = new MimeType(MimeType::HTML . "; charset=utf-8");
        if (@$_GET["flash"]) {
            $this->flash[] = $_GET['flash'];
            unset($_GET["flash"]);
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
