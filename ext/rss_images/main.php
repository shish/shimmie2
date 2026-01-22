<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{LINK};

final class RSSImages extends Extension
{
    public const KEY = "rss_images";
    public function onPostListBuilding(PostListBuildingEvent $event): void
    {
        $title = Ctx::$config->get(SetupConfig::TITLE);

        if (count($event->search_terms) > 0) {
            $search = SearchTerm::implode($event->search_terms);
            Ctx::$page->add_html_header(LINK([
                'rel' => 'alternate',
                'type' => 'application/rss+xml',
                'title' => "$title - Posts with tags: $search",
                'href' => make_link("rss/images/" . url_escape($search) . "/1")
            ]));
        } else {
            Ctx::$page->add_html_header(LINK([
                'rel' => 'alternate',
                'type' => 'application/rss+xml',
                'title' => "$title - Posts",
                'href' => make_link("rss/images/1")
            ]));
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        if (
            $event->page_matches("rss/images", paged: true)
            || $event->page_matches("rss/images/{search}", paged: true)
        ) {
            $search_terms = SearchTerm::explode($event->get_arg('search', ""));
            $page_number = $event->get_iarg('page_num', 1);
            $page_size = Ctx::$config->get(IndexConfig::IMAGES);
            if (Ctx::$config->get(RSSImagesConfig::RSS_LIMIT) && $page_number > 9) {
                return;
            }
            $images = Search::find_images(($page_number - 1) * $page_size, $page_size, $search_terms);
            $this->do_rss($images, $search_terms, $page_number);
        }
    }

    public function onImageInfoSet(ImageInfoSetEvent $event): void
    {
        Ctx::$cache->delete("rss-item-image:{$event->image->id}");
    }

    /**
     * @param Image[] $images
     * @param search-term-array $search_terms
     */
    private function do_rss(array $images, array $search_terms, int $page_number): void
    {
        $data = "";
        foreach ($images as $image) {
            $data .= $this->thumb($image);
        }

        $title = Ctx::$config->get(SetupConfig::TITLE);
        $base_href = Url::base()->asAbsolute();
        $search = "";
        if (count($search_terms) > 0) {
            $search = url_escape(SearchTerm::implode($search_terms)) . "/";
        }

        if ($page_number > 1) {
            $prev_url = make_link("rss/images/$search".($page_number - 1));
            $prev_link = "<atom:link rel=\"previous\" href=\"$prev_url\" />";
        } else {
            $prev_link = "";
        }
        $next_url = make_link("rss/images/$search".($page_number + 1));
        $next_link = "<atom:link rel=\"next\" href=\"$next_url\" />"; // no end...

        $version = SysConfig::getVersion();
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
        Ctx::$page->set_data(MimeType::RSS, $xml);
    }

    private function thumb(Image $image): string
    {
        $cached = Ctx::$cache->get("rss-item-image:{$image->id}");
        if (!is_null($cached)) {
            return $cached;
        }

        $link = make_link("post/view/{$image->id}")->asAbsolute();
        $tags = html_escape($image->get_tag_list());
        $thumb_url = $image->get_thumb_link();
        $image_url = $image->get_image_link();
        $posted = date(DATE_RSS, \Safe\strtotime($image->posted));
        $content = html_escape(
            "<div>" .
            "<p>" . $this->theme->build_thumb($image) . "</p>" .
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

        Ctx::$cache->set("rss-item-image:{$image->id}", $data, rand(43200, 86400));

        return $data;
    }

    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('rss/images'), "Feed", "rss_feed");
        }
    }
}
