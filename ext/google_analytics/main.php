<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\META;

final class GoogleAnalytics extends Extension
{
    public const KEY = "google_analytics";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $google_analytics_id = Ctx::$config->get(GoogleAnalyticsConfig::ANALYTICS_ID);
        if ($google_analytics_id && stristr($google_analytics_id, "UA-")) {
            Ctx::$page->add_html_header(META([
                "name" => "google-analytics-id",
                "content" => $google_analytics_id,
            ]));
        }
    }
}
