<?php
/**
 * Name: Custom HTML Headers
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com
 * License: GPLv2
 * Description: Allows admins to set custom <head> content
 * Documentation:
 *  When you go to board config you can find a block named Custom HTML Headers.
 *  In that block you can simply place any thing you can place within <head></head>
 *  
 *  This can be useful if you want to add website tracking code or other javascript.
 *  NOTE: Only use if you know what you're doing.
 *  
 */
class custom_html_headers extends Extension {
        # Adds setup block for custom <head> content
        public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Custom HTML Headers");
		$sb->add_longtext_option("custom_html_headers", 
                        "HTML Code to place within &lt;head&gt;&lt;/head&gt; on all pages<br>");
		$event->panel->add_block($sb);
	}
        
        # Load Analytics tracking code on page request
        public function onPageRequest(PageRequestEvent $event) {
                global $config, $page;
                
                $header = $config->get_string('custom_html_headers','');
                if ($header!='') $page->add_html_header($header);
        }
}
?>
