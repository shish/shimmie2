<?php

class TwitterSocTheme extends Themelet {
	/*
	 * Show $text on the $page
	 */
	public function display_feed(Page $page, /*string*/ $username) {
		$page->add_block(new Block("Tweets", '
			<div class="tweet_soc"></div>
			<p><a href="http://twitter.com/'.url_escape($username).'">Follow us on Twitter</a>
			<script type="text/javascript">
$(function() {
	 $(".tweet_soc").tweet({
		 username: "'.html_escape($username).'",
		 join_text: "auto",
		 template: "{text} -- {time}",
		 count: 6,
		 loading_text: "loading tweets..."
	 });
});
			 </script>
		', "left", 25));
	}
}

