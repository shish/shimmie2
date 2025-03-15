<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LINK;

final class Holiday extends Extension
{
    public const KEY = "holiday";
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if (date('d/m') == '01/04' && $config->get_bool(HolidayConfig::APRIL_FOOLS)) {
            $page->add_html_header(LINK([
                'rel' => 'stylesheet',
                'href' => Url::base() . '/ext/holiday/stylesheets/aprilfools.css',
                'type' => 'text/css'
            ]));
        }
    }
}
