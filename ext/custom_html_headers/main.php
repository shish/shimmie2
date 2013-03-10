<?php
/**
 * Name: Custom HTML Headers
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com
 * License: GPLv2
 * Description: Allows admins to modify & set custom <head> content
 * Documentation:
 *  When you go to board config you can find a block named Custom HTML Headers.
 *  In that block you can simply place any thing you can place within <head></head>
 *  
 *  This can be useful if you want to add website tracking code or other javascript.
 *  NOTE: Only use if you know what you're doing.
 *  
 *  You can now also add a prefix or suffix to your page title for SEO purposes
 */
class custom_html_headers extends Extension {
        # Adds setup block for custom <head> content
        public function onSetupBuilding(SetupBuildingEvent $event) {
                global $config;
            
		$sb = new SetupBlock("Custom HTML Headers");
                
                // custom headers
		$sb->add_longtext_option("custom_html_headers", 
                        "HTML Code to place within &lt;head&gt;&lt;/head&gt; on all pages<br>");
                
                // modified title
                $sb->add_text_option("title_prefix", "<br>Page Title Prefix ");              
                $sb->add_text_option("title_suffix", "<br>Page Title Suffix ");
                
		$event->panel->add_block($sb);
	}
        
        public function onInitExt(InitExtEvent $event) {
            global $config;
            
            $config->set_default_string("title_prefix", "");
            $config->set_default_string("title_suffix", " - {$config->get_string("title")}");
        }
        
        # Load Analytics tracking code on page request
        public function onPageRequest(PageRequestEvent $event) {
            $this->handle_custom_html_headers();
            $this->handle_modified_page_title();
        }
        
        private function handle_custom_html_headers() {
            global $config, $page;
            
            $header = $config->get_string('custom_html_headers','');
            if ($header!='') $page->add_html_header($header);
        }
        
        private function handle_modified_page_title() {
            global $config, $page;
            
            $page->title = $config->get_string("title_prefix") . 
                    $page->title . $config->get_string("title_suffix");
        }
}
?>
