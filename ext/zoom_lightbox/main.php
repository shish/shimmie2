<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{LINK, SCRIPT};

final class ZoomLightbox extends Extension
{
    public const KEY = "zoom_lightbox";

    #[EventListener]
    public function onDisplayingPost(DisplayingPostEvent $event): void
    {
        $data_href = Url::base();
        Ctx::$page->add_html_header(SCRIPT(["src" => "{$data_href}/ext/zoom_lightbox/glightbox.min.js", "defer" => true]), 10);
        Ctx::$page->add_html_header(LINK([
            "rel" => "stylesheet",
            "type" => "text/css",
            "href" => "{$data_href}/ext/zoom_lightbox/glightbox.min.css"
        ]), 10);
    }
}
