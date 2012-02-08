<?php
/*
 * Name: XML Sitemap
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Adds sitemap.xml on request.
 * Documentation:
 */

class XMLSitemap extends SimpleExtension {
	public function onPageRequest(PageRequestEvent $event) {
		if($event->page_matches("sitemap.xml")) {
			$images = Image::find_images(0, 50, array());
			$this->do_xml($images);
		}
	}
	
	private function do_xml(/*array(Image)*/ $images) {
		global $page;
		$page->set_mode("data");
		$page->set_type("application/xml");

		$data = "";
		foreach($images as $image) {
			$link = make_http(make_link("post/view/{$image->id}"));
			$posted = date("Y-m-d", $image->posted_timestamp);
			
			$data .= "
			<url>
			<loc>$link</loc>
			<lastmod>$posted</lastmod>
			<changefreq>monthly</changefreq>
			<priority>0.8</priority>
			</url>
			";
		}

		$base_href = make_http(make_link("post/list"));

		$xml = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">
				<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
				<url>
				<loc>$base_href</loc>
				<lastmod>2009-01-01</lastmod>
				<changefreq>monthly</changefreq>
				<priority>1</priority>
				</url>
				$data
				</urlset>
				";
		$page->set_data($xml);
	}
}
?>
