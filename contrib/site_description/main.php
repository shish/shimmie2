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
class SiteDescription extends SimpleExtension {
	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page;
		if(strlen($config->get_string("site_description")) > 0) {
			$description = $config->get_string("site_description");
			$page->add_header("<meta name=\"description\" content=\"$description\">");
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Site Description");
		$sb->add_longtext_option("site_description");
		$event->panel->add_block($sb);
	}
}
?>
