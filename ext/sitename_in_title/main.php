<?php

declare(strict_types=1);

namespace Shimmie2;

final class SitenameInTitle extends Extension
{
    public const KEY = "sitename_in_title";

    #[EventListener(priority: 96)] // after index
    public function onPageRequest(PageRequestEvent $event): void
    {
        if (defined("UNITTEST")) {
            return;
        }

        // check sitename is not already in title (can occur on index & other pages)
        $site_title = Ctx::$config->get(SetupConfig::TITLE);
        $sitename_in_title = Ctx::$config->get(SitenameInTitleConfig::SITENAME_IN_TITLE);
        if (!str_contains(Ctx::$page->title, $site_title)) {
            // Make sure header is set to just the page title, because set_title()
            // will set both title and heading if heading is empty
            if (empty(Ctx::$page->heading)) {
                Ctx::$page->set_heading($site_title);
            }
            if ($sitename_in_title === "prefix") {
                Ctx::$page->set_title("$site_title - " . Ctx::$page->title);
            } else {
                Ctx::$page->set_title(Ctx::$page->title . " - $site_title");
            }
        }
    }
}
