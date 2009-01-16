<?php
/**
 * Name: Site Description
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: A description for search engines
 * Documentation:
 *  This extension sets the "description" meta tag in the header
 *  of pages so that search engines can pick it up
 */
class SiteDescription implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof PageRequestEvent) {
			if(strlen($event->context->config->get_string("site_description")) > 0) {
				$description = $event->context->config->get_string("site_description");
				$event->context->page->add_header("<meta name=\"description\" content=\"$description\">");
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
