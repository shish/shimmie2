<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\SCRIPT;

final class GoogleAnalytics extends Extension
{
    public const KEY = "google_analytics";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $google_analytics_id = Ctx::$config->get(GoogleAnalyticsConfig::ANALYTICS_ID);
        if ($google_analytics_id && stristr($google_analytics_id, "UA-")) {
            Ctx::$page->add_html_header(SCRIPT(["type" => 'text/javascript'], "
                var _gaq = _gaq || [];
                _gaq.push(['_setAccount', '$google_analytics_id']);
                _gaq.push(['_trackPageview']);
                (function() {
                    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                    ga.src = ('https:' === document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                })();
            "));
        }
    }
}
