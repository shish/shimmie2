<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\SCRIPT;

class GoogleAnalytics extends Extension
{
    # Add analytics to config
    public function onSetupBuilding(SetupBuildingEvent $event): void
    {
        $event->panel->add_config_group(new GoogleAnalyticsConfig());
    }

    # Load Analytics tracking code on page request
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config, $page;

        $google_analytics_id = $config->get_string(GoogleAnalyticsConfig::ANALYTICS_ID, '');
        if (stristr($google_analytics_id, "UA-")) {
            $page->add_html_header(SCRIPT(["type" => 'text/javascript'], "
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
