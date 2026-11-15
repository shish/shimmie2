<?php

declare(strict_types=1);

namespace Shimmie2;

final class CustomHtmlHeaders extends Extension
{
    public const KEY = "custom_html_headers";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $header = Ctx::$config->get(CustomHtmlHeadersConfig::CUSTOM_HTML_HEADERS);
        if (!empty($header)) {
            Ctx::$page->add_html_header(\MicroHTML\rawHTML($header));
        }
    }
}
