<?php
/**
 * Name: RSS for Images
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Self explanitory
 */

class RSS_Images extends Extension {
// event handling {{{
	public function receive_event($event) {
		if($event instanceof PostListBuildingEvent) {
			global $page;
			global $config;
			$title = $config->get_string('title');

			if(count($event->search_terms) > 0) {
				$search = implode(' ', $event->search_terms);
				$page->add_header("<link rel=\"alternate\" type=\"application/rss+xml\" ".
					"title=\"$title - Images with tags: $search\" href=\"".make_link("rss/images/$search")."\" />");
			}
			else {
				$page->add_header("<link rel=\"alternate\" type=\"application/rss+xml\" ".
					"title=\"$title - Images\" href=\"".make_link("rss/images")."\" />");
			}
		}

		if(($event instanceof PageRequestEvent) && ($event->page_name == "rss")) {
			if($event->get_arg(0) == 'images') {
				global $database;
				if($event->count_args() >= 2) {
					$this->do_rss($database->get_images(0, 12, tag_explode($event->get_arg(1))));
				}
				else {
					$this->do_rss($database->get_images(0, 12));
				}
			}
		}
	}
// }}}
// output {{{
	private function do_rss($images) {
		global $page;
		global $config;
		$page->set_mode("data");
		$page->set_type("application/xml");

		$data = "";
		foreach($images as $image) {
			$link = make_link("post/view/{$image->id}");
			$tags = $image->get_tag_list();
			$owner = $image->get_owner();
			$thumb_url = $image->get_thumb_link();
			$image_url = $image->get_image_link();
			$posted = strftime("%a, %d %b %Y %T %Z", $image->posted_timestamp);
			$content = html_escape(
				"<p>" . Themelet::build_thumb_html($image) . "</p>" .
				"<p>Uploaded by " . $owner->name . "</p>"
			);
			
			$data .= "
		<item>
			<title>{$image->id} - $tags</title>
			<link>$link</link>
			<guid isPermaLink=\"true\">$link</guid>
			<pubDate>$posted</pubDate>
			<description>$content</description>
			<media:thumbnail url=\"$thumb_url\"/>
			<media:content url=\"$image_url\"/>
		</item>
			";
		}

		$title = $config->get_string('title');
		$base_href = $config->get_string('base_href');
		$version = VERSION;
		$xml = "<"."?xml version=\"1.0\" encoding=\"utf-8\" ?".">
<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss\">
    <channel>
        <title>$title</title>
        <description>The latest uploads to the image board</description>
		<link>$base_href</link>
		<generator>Shimmie-$version</generator>
		<copyright>(c) 2007 Shish</copyright>
		$data
	</channel>
</rss>";
		$page->set_data($xml);
	}
// }}}
}
add_event_listener(new RSS_Images());
?>
