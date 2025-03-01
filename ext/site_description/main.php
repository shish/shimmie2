<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{META};

class SiteDescription extends Extension
{
    public const KEY = "site_description";

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;
        if (!empty($config->get_string(SiteDescriptionConfig::DESCRIPTION))) {
            $description = $config->get_string(SiteDescriptionConfig::DESCRIPTION);
            $page->add_html_header(META([
                'name' => 'description',
                'content' => $description
            ]));
        }
        if (!empty($config->get_string(SiteDescriptionConfig::KEYWORDS))) {
            $keywords = $config->get_string(SiteDescriptionConfig::KEYWORDS);
            $page->add_html_header(META([
                'name' => 'keywords',
                'content' => $keywords
            ]));
        }
    }
}
