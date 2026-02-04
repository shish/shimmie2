<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{META};

final class SiteDescription extends Extension
{
    public const KEY = "site_description";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $description = Ctx::$config->get(SiteDescriptionConfig::DESCRIPTION);
        if (!empty($description)) {
            Ctx::$page->add_html_header(META([
                'name' => 'description',
                'content' => $description
            ]));
        }
        $keywords = Ctx::$config->get(SiteDescriptionConfig::KEYWORDS);
        if (!empty($keywords)) {
            Ctx::$page->add_html_header(META([
                'name' => 'keywords',
                'content' => $keywords
            ]));
        }
    }
}
