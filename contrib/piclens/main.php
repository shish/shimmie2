<?php
/**
 * Name: PicLens Button
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Adds a link to piclensify the gallery
 * Documentation:
 *  This extension only provides a button to the javascript
 *  version of the gallery; the "RSS for Images" extension
 *  is piclens-compatible to start with. (And that extension
 *  must be active for this one to do anything useful)
 */
class PicLens implements Extension {
	public function receive_event(Event $event) {
		if($event instanceof PageRequestEvent) {
			$event->page->add_header("<script type=\"text/javascript\" src=\"http://lite.piclens.com/current/piclens.js\"></script>");
		}
		if($event instanceof PostListBuildingEvent) {
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
