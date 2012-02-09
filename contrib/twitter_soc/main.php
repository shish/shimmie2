<?php
/*
 * Name: Tweet!
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Show a twitter feed with the Sea of Clouds script
 */

class TwitterSoc extends Extension {
	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $page;
		if(strlen($config->get_string("twitter_soc_username")) > 0) {
			$this->theme->display_feed($page, $config->get_string("twitter_soc_username"));
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Tweet!");
		$sb->add_text_option("twitter_soc_username", "Username ");
		$event->panel->add_block($sb);
	}
}
?>
