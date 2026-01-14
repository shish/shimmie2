<?php

namespace Shimmie2;

use function MicroHTML\{LINK, SCRIPT};

final class GLightbox extends Extension
{
    public const string KEY = GLightboxInfo::KEY;

    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        $data_href = Url::base();

        Ctx::$page->add_html_header(SCRIPT(["src" => "{$data_href}/ext/glightbox/glightbox.min.js"]), 10);
        Ctx::$page->add_html_header(LINK([
            "rel" => "stylesheet",
            "type" => "text/css",
            "href" => "{$data_href}/ext/glightbox/glightbox.min.css"
        ]), 10);
    }
}
