<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\LINK;

final class Holiday extends Extension
{
    public const KEY = "holiday";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (date('m/d') === '04/01' && Ctx::$config->get(HolidayConfig::APRIL_FOOLS)) {
            Ctx::$page->add_html_header(LINK([
                'rel' => 'stylesheet',
                'href' => Url::base() . '/ext/holiday/stylesheets/aprilfools.css',
                'type' => 'text/css'
            ]));
        }
    }
}
