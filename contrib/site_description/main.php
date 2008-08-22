<?php
/**
 * Name: Site Description
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Sets the "description" meta-info in the page header, for
 *              eg search engines to read
 */
class SiteDescription extends Extension {
	public function receive_event($event) {
		if($event instanceof PageRequestEvent) {
			global $config;
			if(strlen($config->get_string("site_description")) > 0) {
				$description = $config->get_string("site_description");
				$event->page->add_header("<meta name=\"description\" content=\"$description\">");
			}
		}
		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Site Description");
			$sb->add_longtext_option("site_description");
			$event->panel->add_block($sb);
		}
	}
}
add_event_listener(new SiteDescription());
?>
