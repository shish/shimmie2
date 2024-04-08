<?php

declare(strict_types=1);

namespace Shimmie2;

class RSSImages extends Extension
{
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        global $config, $page;
        $title = $config->get_string(SetupConfig::TITLE);

        if (count($event->search_terms) > 0) {
            $search = url_escape(Tag::implode($event->search_terms));
            $page->add_html_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
                "title=\"$title - Posts with tags: $search\" href=\"".make_link("rss/images/$search/1")."\" />");
        } else {
            $page->add_html_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
                "title=\"$title - Posts\" href=\"".make_link("rss/images/1")."\" />");
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $config;
        if (
            $event->page_matches("rss/images", paged: true)
            || $event->page_matches("rss/images/{search}", paged: true)
        ) {
            $search_terms = Tag::explode($event->get_arg('search', ""));
            $page_number = $event->get_iarg('page_num', 1);
            $page_size = $config->get_int(IndexConfig::IMAGES);
            if (SPEED_HAX && $page_number > 9) {
                return;
            }
            $images = Search::find_images(($page_number - 1) * $page_size, $page_size, $search_terms);
            $this->do_rss($images, $search_terms, $page_number);
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        global $cache;
        $cache->delete("rss-item-image:{$event->image->id}");
    }

    /**
     * @param Image[] $images
     * @param string[] $search_terms
     */
    private function do_rss(array $images, array $search_terms, int $page_number): void
    {
        global $page;
        global $config;
        $page->set_mode(PageMode::DATA);
        $page->set_mime(MimeType::RSS);

        $data = "";
        foreach ($images as $image) {
            $data .= $this->thumb($image);
        }

        $title = $config->get_string(SetupConfig::TITLE);
        $base_href = make_http(get_base_href());
        $search = "";
        if (count($search_terms) > 0) {
            $search = url_escape(Tag::implode($search_terms)) . "/";
        }

        if ($page_number > 1) {
            $prev_url = make_link("rss/images/$search".($page_number - 1));
            $prev_link = "<atom:link rel=\"previous\" href=\"$prev_url\" />";
        } else {
            $prev_link = "";
        }
        $next_url = make_link("rss/images/$search".($page_number + 1));
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

    private function thumb(Image $image): string
    {
        global $cache;

        $cached = $cache->get("rss-item-image:{$image->id}");
        if (!is_null($cached)) {
            return $cached;
        }

        $link = make_http(make_link("post/view/{$image->id}"));
        $tags = html_escape($image->get_tag_list());
        $thumb_url = $image->get_thumb_link();
        $image_url = $image->get_image_link();
        $posted = date(DATE_RSS, \Safe\strtotime($image->posted));
        $content = html_escape(
            "<div>" .
            "<p>" . $this->theme->build_thumb_html($image) . "</p>" .
            "</div>"
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

        $cache->set("rss-item-image:{$image->id}", $data, rand(43200, 86400));

        return $data;
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent == "posts") {
            $event->add_nav_link("posts_rss", new Link('rss/images'), "Feed");
        }
    }
}
