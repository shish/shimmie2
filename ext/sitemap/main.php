<?php
/*
 * Name: XML Sitemap
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Adds sitemap.xml on request.
 * Documentation:
 */

class XMLSitemap extends Extension {
        private $sitemap_queue = "";
    
	public function onPageRequest(PageRequestEvent $event) { 
            global $database, $config;
            if($event->page_matches("sitemap.xml")) 
            {            
                // add index
                $index[0] = $base_href = $config->get_string("front_page");
                $this->add_sitemap_queue($index, "weekly", "1");

                /* --- Add 20 most used tags --- */
                $popular_tags = $database->get_all("SELECT tag, count FROM tags ORDER BY `count` DESC LIMIT 0,20");
                foreach($popular_tags as $arrayid => $tag) {
                    $tag = $tag['tag'];
                    $popular_tags[$arrayid] = "post/list/$tag/";
                }
                $this->add_sitemap_queue($popular_tags, "monthly", "0.9" /* not sure how to deal with date here */);           

                /* --- Add latest images to sitemap with higher priority --- */
                $latestimages = Image::find_images(0, 50, array());
                $latestimages_urllist = array();
                foreach($latestimages as $arrayid => $image)
                    // create url from image id's
                    $latestimages_urllist[$arrayid] = "post/view/$image->id";
                $this->add_sitemap_queue($latestimages_urllist, "monthly", "0.8", date("Y-m-d", $image->posted_timestamp));

                /* --- Add other tags --- */
                $other_tags = $database->get_all("SELECT tag, count FROM tags ORDER BY `count` DESC LIMIT 21,10000000");
                foreach($other_tags as $arrayid => $tag) {
                    $tag = $tag['tag'];
                    // create url from tags (tagme ignored)
                    if ($tag != "tagme")
                        $other_tags[$arrayid] = "post/list/$tag/";
                }
                $this->add_sitemap_queue($other_tags, "monthly", "0.7" /* not sure how to deal with date here */);

                /* --- Add all other images to sitemap with lower priority --- */
                $otherimages = Image::find_images(51, 10000000, array());
                foreach($otherimages as $arrayid => $image)
                    // create url from image id's
                    $otherimages[$arrayid] = "post/view/$image->id";             
                $this->add_sitemap_queue($otherimages, "monthly", "0.6", date("Y-m-d", $image->posted_timestamp));
          
                
                /* --- Display page --- */
                // when sitemap is ok, display it from the file
                $this->display_sitemap();
            } 
	}
	
        // Adds an array of urls to the sitemap with the given information
	private function add_sitemap_queue(/*array(urls)*/ $urls, $changefreq="monthly", $priority="0.5", $date="2013-02-01") {
                foreach($urls as $url) {
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
        
        // sets sitemap with entries in the queue
        private function display_sitemap()
        {
            global $page;
            $page->set_mode("data");
            $page->set_type("application/xml");
             
            $xml = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">
                <urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"> 
                    $this->sitemap_queue
                </urlset>";
            
            // sets
            $page->set_data($xml);
        }
}
?>
