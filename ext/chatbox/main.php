<?php
/**
 * Name: Chatbox
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com
 * License: GPLv2
 * Description: Places an ajax chatbox at the bottom of each page
 * Documentation:
 *  This chatbox uses YShout 5 as core.
 * 
 * 
 */
class chatbox extends Extension {
        # Add analytics to config
        public function onSetupBuilding(SetupBuildingEvent $event) {
		//$sb = new SetupBlock("Google Analytics");
		//$sb->add_text_option("google_analytics_id", "Analytics ID: ");
                //$sb->add_label("<br>(eg. UA-xxxxxxxx-x)");
		
		//$event->panel->add_block($sb);
	}
        
        # Load Analytics tracking code on page request
        public function onPageRequest(PageRequestEvent $event) {
                global $page;

                // Adds header to enable chatbox
                $root = make_http();
                $yPath = "$root/ext/chatbox/";
                $page->add_html_header("<script src=\"$root/ext/chatbox/js/jquery.js\" type=\"text/javascript\"></script>
                <script src=\"$root/ext/chatbox/js/yshout.js\" type=\"text/javascript\"></script>
                
                
                <link rel=\"stylesheet\" href=\"$root/ext/chatbox/css/dark.yshout.css\" />

                <script type=\"text/javascript\">
                   new YShout({ yPath: '$yPath' });
                </script>");
                
                // loads the chatbox at the set location    
                $html = "<div id=\"yshout\"></div>";
                $chatblock = new Block("Chatbox (Beta)", $html, "main", 97);
                $page->add_block($chatblock);
        }
}
?>
