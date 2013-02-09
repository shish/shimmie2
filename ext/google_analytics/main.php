<?php
/**
 * Name: Google Analytics
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://drudexsoftware.com
 * License: GPLv2
 * Description: Integrates Google Analytics tracking
 * Documentation:
 *  User has to enter their Google Analytics ID in the Board Config to use this extention.
 */
class google_analytics extends Extension {
        # Add analytics to config
        public function onSetupBuilding(SetupBuildingEvent $event) {
		global $config;

		$sb = new SetupBlock("Google Analytics");
		$sb->add_text_option("google_analytics_id", "Analytics ID: ");
                $sb->add_label("<br>(eg. UA-xxxxxxxx-x)");
		
		$event->panel->add_block($sb);
	}
        
        # Load Analytics tracking code on page request
        public function onPageRequest(PageRequestEvent $event) {
                global $config;
                global $page;
                
                $google_analytics_id = $config->get_string('google_analytics_id','');
                if (stristr($google_analytics_id, "UA-"))
                {
                    $page->add_html_header("<script type='text/javascript'>
                    var _gaq = _gaq || [];
                    _gaq.push(['_setAccount', '$google_analytics_id']);
                    _gaq.push(['_trackPageview']);
                    (function() {
                      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                    })();</script>");
                }
        }
}
?>
