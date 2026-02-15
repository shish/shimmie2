<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, LINK, P};

use MicroHTML\HTMLElement;

final class RSSImages extends Extension
{
    public const KEY = "rss_images";

    #[EventListener]
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

    #[EventListener]
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

    #[EventListener]
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
        $items = [];
        foreach ($images as $image) {
            $items[] = $this->thumb($image);
        }

        $title = Ctx::$config->get(SetupConfig::TITLE);
        $base_href = Url::base()->asAbsolute();
        $search = "";
        if (count($search_terms) > 0) {
            $search = url_escape(SearchTerm::implode($search_terms)) . "/";
        }

        $links = [];
        if ($page_number > 1) {
            $prev_url = make_link("rss/images/$search".($page_number - 1));
            $links[] = ATOM_LINK(["rel" => "previous", "href" => (string)$prev_url]);
        }
        $next_url = make_link("rss/images/$search".($page_number + 1));
        $links[] = ATOM_LINK(["rel" => "next", "href" => (string)$next_url]); // no end...

        $version = SysConfig::getVersion();

        $rss = RSS(
            ["version" => "2.0", "xmlns:media" => "http://search.yahoo.com/mrss/", "xmlns:atom" => "http://www.w3.org/2005/Atom"],
            CHANNEL(
                RSS_TITLE($title),
                RSS_DESCRIPTION("The latest uploads to the image board"),
                RSS_LINK($base_href),
                RSS_GENERATOR("Shimmie-$version"),
                RSS_COPYRIGHT("(c) 2007 Shish"),
                ...$links,
                ...$items
            )
        );

        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n" . $rss;
        Ctx::$page->set_data(MimeType::RSS, $xml);
    }

    private function thumb(Image $image): HTMLElement
    {
        $link = make_link("post/view/{$image->id}")->asAbsolute();
        $tags = $image->get_tag_list();
        $thumb_url = $image->get_thumb_link()->asAbsolute();
        $image_url = $image->get_image_link()->asAbsolute();
        $posted = date(DATE_RSS, \Safe\strtotime($image->posted));
        $content = (string)DIV(P($this->theme->build_thumb($image)));

        return ITEM(
            RSS_TITLE("{$image->id} - $tags"),
            RSS_LINK($link),
            RSS_GUID(["isPermaLink" => "true"], (string)$link),
            RSS_PUBDATE($posted),
            RSS_DESCRIPTION($content),
            MEDIA_THUMBNAIL(["url" => (string)$thumb_url]),
            MEDIA_CONTENT(["url" => (string)$image_url])
        );
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "posts") {
            $event->add_nav_link(make_link('rss/images'), "Feed");
        }
    }
}
