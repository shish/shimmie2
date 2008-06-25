<?php
/**
 * Name: PicLens Button
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Adds a link to piclensify the gallery
 */
class PicLens extends Extension {
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent')) {
			global $config;
			if(strlen($config->get_string("site_description")) > 0) {
				$description = $config->get_string("site_description");
				$event->page->add_header("<script type=\"text/javascript\" src=\"http://lite.piclens.com/current/piclens.js\"></script>");
			}
		}
		if(is_a($event, 'PostListBuildingEvent')) {
			$foo='
				<a href="javascript:PicLensLite.start();">Start Slideshow 
				<img src="http://lite.piclens.com/images/PicLensButton.png" 
					alt="PicLens" width="16" height="12" border="0" 
					align="absmiddle"></a>';
			$event->page->add_block(new Block("PicLens", $foo, "left", 20));
		}
	}
}
add_event_listener(new PicLens());
?>
