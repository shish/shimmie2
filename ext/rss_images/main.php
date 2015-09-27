<?php
/*
 * Name: RSS for Images
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Self explanatory
 */

class RSS_Images extends Extension {
	public function onPostListBuilding(PostListBuildingEvent $event) {
		global $config, $page;
		$title = $config->get_string('title');

		if(count($event->search_terms) > 0) {
			$search = html_escape(implode(' ', $event->search_terms));
			$page->add_html_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
				"title=\"$title - Images with tags: $search\" href=\"".make_link("rss/images/$search/1")."\" />");
		}
		else {
			$page->add_html_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
				"title=\"$title - Images\" href=\"".make_link("rss/images/1")."\" />");
		}
	}

	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("rss/images")) {
			$search_terms = $event->get_search_terms();
			$page_number = $event->get_page_number();
			$page_size = $event->get_page_size();
			$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
			$this->do_rss($images, $search_terms, $page_number);
		}
	}

	/**
	 * @param array $images
	 * @param array $search_terms
	 * @param int $page_number
	 */
	private function do_rss($images, $search_terms, /*int*/ $page_number) {
		global $page;
		global $config;
		$page->set_mode("data");
		$page->set_type("application/rss+xml");

		$data = "";
		foreach($images as $image) {
			$data .= $this->thumb($image);
		}

		$title = $config->get_string('title');
		$base_href = make_http(get_base_href());
		$search = "";
		if(count($search_terms) > 0) {
			$search = url_escape(implode(" ", $search_terms)) . "/";
		}

		if($page_number > 1) {
			$prev_url = make_link("rss/images/$search".($page_number-1));
			$prev_link = "<atom:link rel=\"previous\" href=\"$prev_url\" />";
		}
		else {
			$prev_link = "";
		}
		$next_url = make_link("rss/images/$search".($page_number+1));
		$next_link = "<atom:link rel=\"next\" href=\"$next_url\" />"; // no end...

		$version = VERSION;
		$xml = "<"."?xml version=\"1.0\" encoding=\"utf-8\" ?".">
<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
    <channel>
        <title>$title</title>
        <description>The latest uploads to the image board</description>
		<link>$base_href</link>
		<generator>Shimmie-$version</generator>
		<copyright>(c) 2007 Shish</copyright>
		$prev_link
		$next_link
		$data
	</channel>
</rss>";
		$page->set_data($xml);
	}

	/**
	 * @param Image $image
	 * @return string
	 */
	private function thumb(Image $image) {
		global $database;

		$cached = $database->cache->get("rss-thumb:{$image->id}");
		if($cached) return $cached;

		$link = make_http(make_link("post/view/{$image->id}"));
		$tags = html_escape($image->get_tag_list());
		$owner = $image->get_owner();
		$thumb_url = $image->get_thumb_link();
		$image_url = $image->get_image_link();
		$posted = date(DATE_RSS, strtotime($image->posted));
		$content = html_escape(
			"<p>" . $this->theme->build_thumb_html($image) . "</p>" .
			"<p>Uploaded by " . html_escape($owner->name) . "</p>"
		);

		$data = "
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

		$database->cache->set("rss-thumb:{$image->id}", $data, 3600);

		return $data;
	}
}

