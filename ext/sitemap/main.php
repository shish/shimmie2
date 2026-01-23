<?php

declare(strict_types=1);

namespace Shimmie2;

final class XMLSitemapURL
{
    public function __construct(
        public Url $url,
        public string $changefreq,
        public string $priority,
        public string $date
    ) {
    }
}

final class XMLSitemap extends Extension
{
    public const KEY = "sitemap";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("sitemap.xml")) {
            $cache_path = Filesystem::data_path("cache/sitemap.xml");

            if ($this->new_sitemap_needed($cache_path)) {
                $xml = $this->handle_full_sitemap();
                $cache_path->put_contents($xml);
            }

            $xml = $cache_path->get_contents();
            Ctx::$page->set_data(MimeType::XML_APPLICATION, $xml);
        }
    }

    // Full sitemap
    private function handle_full_sitemap(): string
    {
        global $database;

        $urls = [];

        // add index
        $urls[] = new XMLSitemapURL(
            make_link(Ctx::$config->get(SetupConfig::FRONT_PAGE)),
            "weekly",
            "1",
            date("Y-m-d")
        );

        /* --- Add 20 most used tags --- */
        foreach ($database->get_col("SELECT tag FROM tags ORDER BY count DESC LIMIT 20") as $tag) {
            $urls[] = new XMLSitemapURL(
                search_link([$tag]),
                "weekly",
                "0.9",
                date("Y-m-d")
            );
        }

        /* --- Add latest images to sitemap with higher priority --- */
        foreach (Search::find_images(limit: 50) as $image) {
            $urls[] = new XMLSitemapURL(
                make_link("post/view/$image->id"),
                "weekly",
                "0.8",
                date("Y-m-d", \Safe\strtotime($image->posted))
            );
        }

        /* --- Add other tags --- */
        foreach ($database->get_col("SELECT tag FROM tags ORDER BY count DESC LIMIT 10000 OFFSET 21") as $tag) {
            $urls[] = new XMLSitemapURL(
                search_link([$tag]),
                "weekly",
                "0.7",
                date("Y-m-d")
            );
        }

        /* --- Add all other images to sitemap with lower priority --- */
        foreach (Search::find_images(offset: 51, limit: 10000) as $image) {
            $urls[] = new XMLSitemapURL(
                make_link("post/view/$image->id"),
                "monthly",
                "0.6",
                date("Y-m-d", \Safe\strtotime($image->posted))
            );
        }

        /* --- Display page --- */
        return $this->generate_sitemap($urls);
    }

    /**
     * @param XMLSitemapURL[] $urls
     */
    private function generate_sitemap(array $urls): string
    {
        $xml = "<" . "?xml version=\"1.0\" encoding=\"utf-8\"?" . ">\n" .
        "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $url) {
            $link = (string)$url->url->asAbsolute();
            $xml .= "
    <url>
        <loc>$link</loc>
        <lastmod>$url->date</lastmod>
        <changefreq>$url->changefreq</changefreq>
        <priority>$url->priority</priority>
    </url>
";
        }
        $xml .= "</urlset>\n";

        return $xml;
    }

    /**
     * Returns true if a new sitemap is needed.
     */
    private function new_sitemap_needed(Path $cache_path): bool
    {
        if (!$cache_path->exists()) {
            return true;
        }

        $sitemap_generation_interval = 86400; // allow new site map every day
        $last_generated_time = $cache_path->filemtime();

        // if it's been a day since last sitemap creation, return true
        return ($last_generated_time + $sitemap_generation_interval < time());
    }
}
