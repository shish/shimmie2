<?php
/**
 * Name: Site Description
 * Author: Shish <webmaster@shishnet.org>
 * Description: Sets the "description" meta-info in the page header, for
 *              eg search engines to read
 */
class SiteDescription extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent')) {
			global $page, $config;
			if(strlen($config->get_string("site_description")) > 0) {
				$description = $config->get_string("site_description");
				$page->add_header("<meta name=\"description\" content=\"$description\">");
			}
		}
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("Site Description");
			$sb->add_longtext_option("site_description");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_string_from_post("site_description");
		}
	}
}
add_event_listener(new SiteDescription());
?>
