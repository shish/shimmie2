<?php

declare(strict_types=1);

namespace Shimmie2;

class XMLSitemapURL
{
    public function __construct(
        public string $url,
        public string $changefreq,
        public string $priority,
        public string $date
    ) {
    }
}

class XMLSitemap extends Extension
{
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("sitemap.xml")) {
            global $config, $page;

            $cache_path = data_path("cache/sitemap.xml");

            if ($this->new_sitemap_needed($cache_path)) {
                $xml = $this->handle_full_sitemap();
                file_put_contents($cache_path, $xml);
            }

            $xml = \Safe\file_get_contents($cache_path);
            $page->set_mode(PageMode::DATA);
            $page->set_mime(MimeType::XML_APPLICATION);
            $page->set_data($xml);
        }
    }

    // Full sitemap
    private function handle_full_sitemap(): string
    {
        global $database, $config;

        $urls = [];

        // add index
        $urls[] = new XMLSitemapURL(
            $config->get_string(SetupConfig::FRONT_PAGE),
            "weekly",
            "1",
            date("Y-m-d")
        );

        /* --- Add 20 most used tags --- */
        foreach ($database->get_col("SELECT tag FROM tags ORDER BY count DESC LIMIT 20") as $tag) {
            $urls[] = new XMLSitemapURL(
                "post/list/$tag/1",
                "weekly",
                "0.9",
                date("Y-m-d")
            );
        }

        /* --- Add latest images to sitemap with higher priority --- */
        foreach(Search::find_images(limit: 50) as $image) {
            $urls[] = new XMLSitemapURL(
                "post/view/$image->id",
                "weekly",
                "0.8",
                date("Y-m-d", \Safe\strtotime($image->posted))
            );
        }

        /* --- Add other tags --- */
        foreach ($database->get_col("SELECT tag FROM tags ORDER BY count DESC LIMIT 10000 OFFSET 21") as $tag) {
            $urls[] = new XMLSitemapURL(
                "post/list/$tag/1",
                "weekly",
                "0.7",
                date("Y-m-d")
            );
        }

        /* --- Add all other images to sitemap with lower priority --- */
        foreach(Search::find_images(offset: 51, limit: 10000) as $image) {
            $urls[] = new XMLSitemapURL(
                "post/view/$image->id",
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
        foreach($urls as $url) {
            $link = make_http(make_link($url->url));
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
    private function new_sitemap_needed(string $cache_path): bool
    {
        if (!file_exists($cache_path)) {
            return true;
        }

        $sitemap_generation_interval = 86400; // allow new site map every day
        $last_generated_time = filemtime($cache_path);

        // if file doesn't exist, return true
        if ($last_generated_time == false) {
            return true;
        }

        // if it's been a day since last sitemap creation, return true
        return ($last_generated_time + $sitemap_generation_interval < time());
    }
}
