<?php

declare(strict_types=1);

namespace Shimmie2;

final class CustomHtmlHeaders extends Extension
{
    public const KEY = "custom_html_headers";

    #[EventListener(priority: 96)] // after index
    public function onPageRequest(PageRequestEvent $event): void
    {
        $page = Ctx::$page;
        $header = Ctx::$config->get(CustomHtmlHeadersConfig::CUSTOM_HTML_HEADERS);
        if (!empty($header)) {
            $page->add_html_header(\MicroHTML\rawHTML($header));
        }

        // check sitename is not already in title (can occur on index & other pages)
        $site_title = Ctx::$config->get(SetupConfig::TITLE);
        $sitename_in_title = Ctx::$config->get(CustomHtmlHeadersConfig::SITENAME_IN_TITLE);
        if (!str_contains($page->title, $site_title)) {
            // Make sure header is set to just the page title, because set_title()
            // will set both title and heading if heading is empty
            if (empty($page->heading)) {
                $page->set_heading($site_title);
            }
            if ($sitename_in_title === "prefix") {
                $page->set_title("$site_title - $page->title");
            } elseif ($sitename_in_title === "suffix") {
                $page->set_title("$page->title - $site_title");
            }
        }
    }
}
