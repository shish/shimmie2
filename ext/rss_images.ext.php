<?php

class RSS_Images extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "index")) {
			global $page;
			global $config;
			$title = $config->get_string('title');

			$page->add_header("<link rel=\"alternate\" type=\"application/rss+xml\"
				title=\"$title\" href=\"".make_link("rss/images")."\" />");
		}
		if(is_a($event, 'PageRequestEvent') && ($event->page == "rss")) {
			if($event->get_arg(0) == 'images') {
				global $database;
				$this->do_rss($database->get_images(0, 12));
			}
		}
	}
// }}}
// output {{{
	private function do_rss($images) {
		// TODO: this function
		global $page;
		global $config;
		$page->set_mode("data");
		$page->set_type("application/xml");

		$data = "";
		foreach($images as $image) {
			$link = make_link("post/view/{$image->id}");
			$tags = $image->get_tag_list();
			$owner = $image->get_owner();
			$posted = strftime("%a, %d %b %Y %T %Z", $image->posted_timestamp);
			$content = html_escape(
				"<p>" . build_thumb_html($image) . "</p>" .
				"<p>Uploaded by " . $owner->name . "</p>"
			);
			
			$data .= "
				<item>
					<title>{$image->id} - $tags</title>
					<link>$link</link>
					<guid isPermaLink=\"true\">$link</guid>
					<pubDate>$posted</pubDate>
					<description>$content</description>
				</item>
			";
		}

		$title = $config->get_string('title');
		$base_href = $config->get_string('base_href');
		$version = $config->get_string('version');
		$xml = <<<EOD
<?xml version='1.0' encoding='utf-8' ?>
<rss version="2.0">
    <channel>
        <title>$title</title>
        <description>The latest uploads to the image board</description>
		<link>$base_href</link>
		<generator>$version</generator>
		<copyright>(c) 2007 Shish</copyright>
		$data
	</channel>
</rss>
EOD;
		$page->set_data($xml);
	}
// }}}
}
add_event_listener(new RSS_Images());
?>
