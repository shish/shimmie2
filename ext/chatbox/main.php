<?php
/**
 * Name: [Beta] Chatbox
 * Author: Drudex Software <support@drudexsoftware.com>
 * Link: http://www.drudexsoftware.com
 * License: GPLv2
 * Description: Places an ajax chatbox at the bottom of each page
 * Documentation:
 *  This chatbox uses YShout 5 as core.
 */
class Chatbox extends Extension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user;

		// Adds header to enable chatbox
		$root = make_http();
		$yPath = "$root/ext/chatbox/";
		$page->add_html_header("
				<script src=\"$root/ext/chatbox/js/jquery.js\" type=\"text/javascript\"></script>
				<script src=\"$root/ext/chatbox/js/yshout.js\" type=\"text/javascript\"></script>

				<link rel=\"stylesheet\" href=\"$root/ext/chatbox/css/dark.yshout.css\" />

				<script type=\"text/javascript\">
				nickname = '{$user->name}';
				new YShout({ yPath: '$yPath' });
				</script>
		");

		// loads the chatbox at the set location    
		$html = "<div id=\"yshout\"></div>";
		$chatblock = new Block("Chatbox", $html, "main", 97);
		$page->add_block($chatblock);
	}
}
?>
