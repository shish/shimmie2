<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class CustomHtmlHeaders extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        $header = $config->get_string(CustomHtmlHeadersConfig::CUSTOM_HTML_HEADERS, '');
        if ($header != '') {
            $page->add_html_header(rawHTML($header));
        }

        // check sitename is not already in title (can occur on index & other pages)
        $site_title = $config->get_string(SetupConfig::TITLE);
        $sitename_in_title = $config->get_string(CustomHtmlHeadersConfig::SITENAME_IN_TITLE);
        if (!str_contains($page->title, $site_title)) {
            if ($sitename_in_title == "prefix") {
                $page->title = "$site_title - $page->title";
            } elseif ($sitename_in_title == "suffix") {
                $page->title = "$page->title - $site_title";
            }
        }
    }
}
