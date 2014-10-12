<?php
/*
 * Name: Tweet!
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Show a twitter feed with the Sea of Clouds script
 */

class TwitterSoc extends Extension {
	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $page;
		if(strlen($config->get_string("twitter_soc_username")) > 0) {
			$this->theme->display_feed(
				$page,
				$config->get_string("twitter_soc_username"),
				$config->get_int("twitter_soc_count", 6)
			);
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Tweet!");
		$sb->add_text_option("twitter_soc_username", "Username ");
		$sb->add_int_option("twitter_soc_count", "<br>Number of tweets ");
		$event->panel->add_block($sb);
	}
}

