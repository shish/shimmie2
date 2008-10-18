<?php
class RSSImagesTest extends WebTestCase {
    function testImageFeed() {
        $this->get('http://shimmie.shishnet.org/v2/rss/images');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");
    }
}
?>
