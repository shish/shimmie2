<?php

declare(strict_types=1);

namespace Shimmie2;

class XMLSitemap extends Extension
{
    private string $sitemap_queue = "";
    private string $sitemap_filepath = ""; // set onPageRequest

    public function onPageRequest(PageRequestEvent $event)
    {
        if ($event->page_matches("sitemap.xml")) {
            global $config;

            $this->sitemap_filepath = data_path("cache/sitemap.xml");
            // determine if new sitemap needs to be generated
            if ($this->new_sitemap_needed()) {
                // determine which type of sitemap to generate
                if ($config->get_bool("sitemap_generatefull", false)) {
                    $this->handle_full_sitemap(); // default false until cache fixed
                } else {
                    $this->handle_smaller_sitemap();
                }
            } else {
                $this->display_existing_sitemap();
            }
        }
    }

    public function onSetupBuilding(SetupBuildingEvent $event)
    {
        $sb = $event->panel->create_new_block("Sitemap");

        $sb->add_bool_option("sitemap_generatefull", "Generate full sitemap");
        $sb->add_label("<br>(Enabled: every image and tag in sitemap, generation takes longer)");
        $sb->add_label("<br>(Disabled: only display the last 50 uploads in the sitemap)");
    }

    // sitemap with only the latest 50 images
    private function handle_smaller_sitemap()
    {
        /* --- Add latest images to sitemap with higher priority --- */
        $latestimages = Search::find_images(limit: 50);
        if (empty($latestimages)) {
            return;
        }
        $latestimages_urllist = [];
        $last_image = null;
        foreach ($latestimages as $arrayid => $image) {
            // create url from image id's
            $latestimages_urllist[$arrayid] = "post/view/$image->id";
            $last_image = $image;
        }

        $this->add_sitemap_queue(
            $latestimages_urllist,
            "monthly",
            "0.8",
            date("Y-m-d", strtotime($last_image->posted))
        );

        /* --- Display page --- */
        // when sitemap is ok, display it from the file
        $this->generate_display_sitemap();
    }

    // Full sitemap
    private function handle_full_sitemap()
    {
        global $database, $config;

        // add index
        $index = [];
        $index[0] = $config->get_string(SetupConfig::FRONT_PAGE);
        $this->add_sitemap_queue($index, "weekly", "1");

        /* --- Add 20 most used tags --- */
        $popular_tags = $database->get_all("SELECT tag, count FROM tags ORDER BY `count` DESC LIMIT 0,20");
        foreach ($popular_tags as $arrayid => $tag) {
            $tag = $tag['tag'];
            $popular_tags[$arrayid] = "post/list/$tag/";
        }
        $this->add_sitemap_queue($popular_tags, "monthly", "0.9" /* not sure how to deal with date here */);

        /* --- Add latest images to sitemap with higher priority --- */
        $latestimages = Search::find_images(limit: 50);
        $latestimages_urllist = [];
        $latest_image = null;
        foreach ($latestimages as $arrayid => $image) {
            // create url from image id's
            $latestimages_urllist[$arrayid] = "post/view/$image->id";
            $latest_image = $image;
        }
        $this->add_sitemap_queue($latestimages_urllist, "monthly", "0.8", date("Y-m-d", strtotime($latest_image->posted)));

        /* --- Add other tags --- */
        $other_tags = $database->get_all("SELECT tag, count FROM tags ORDER BY `count` DESC LIMIT 21,10000000");
        foreach ($other_tags as $arrayid => $tag) {
            $tag = $tag['tag'];
            // create url from tags (tagme ignored)
            if ($tag != "tagme") {
                $other_tags[$arrayid] = "post/list/$tag/";
            }
        }
        $this->add_sitemap_queue($other_tags, "monthly", "0.7" /* not sure how to deal with date here */);

        /* --- Add all other images to sitemap with lower priority --- */
        $otherimages = Search::find_images(offset: 51, limit: 10000000);
        $image = null;
        foreach ($otherimages as $arrayid => $image) {
            // create url from image id's
            $otherimages[$arrayid] = "post/view/$image->id";
        }
        assert(!is_null($image));
        $this->add_sitemap_queue($otherimages, "monthly", "0.6", date("Y-m-d", strtotime($image->posted)));


        /* --- Display page --- */
        // when sitemap is ok, display it from the file
        $this->generate_display_sitemap();
    }

    /**
     * Adds an array of urls to the sitemap with the given information.
     */
    private function add_sitemap_queue(
        array $urls,
        string $changefreq = "monthly",
        string $priority = "0.5",
        string $date = "2013-02-01"
    ) {
        foreach ($urls as $url) {
            $link = make_http(make_link("$url"));
            $this->sitemap_queue .= "
                    <url>
                    <loc>$link</loc>
                    <lastmod>$date</lastmod>
                    <changefreq>$changefreq</changefreq>
                    <priority>$priority</priority>
                    </url>";
        }
    }

    // sets sitemap with entries in sitemap_queue
    private function generate_display_sitemap()
    {
        global $page;

        $xml = "<" . "?xml version=\"1.0\" encoding=\"utf-8\"?" . ">
				<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
					$this->sitemap_queue
				</urlset>";

        // Generate new sitemap
        file_put_contents($this->sitemap_filepath, $xml);
        $page->set_mode(PageMode::DATA);
        $page->set_mime(MimeType::XML_APPLICATION);
        $page->set_data($xml);
    }

    /**
     * Returns true if a new sitemap is needed.
     */
    private function new_sitemap_needed(): bool
    {
        if (!file_exists($this->sitemap_filepath)) {
            return true;
        }

        $sitemap_generation_interval = 86400; // allow new site map every day
        $last_generated_time = filemtime($this->sitemap_filepath);

        // if file doesn't exist, return true
        if ($last_generated_time == false) {
            return true;
        }

        // if it's been a day since last sitemap creation, return true
        if ($last_generated_time + $sitemap_generation_interval < time()) {
            return true;
        } else {
            return false;
        }
    }

    private function display_existing_sitemap()
    {
        global $page;

        $xml = file_get_contents($this->sitemap_filepath);

        $page->set_mode(PageMode::DATA);
        $page->set_mime(MimeType::XML_APPLICATION);
        $page->set_data($xml);
    }
}
