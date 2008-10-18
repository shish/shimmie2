<?php
class RSSImagesTest extends WebTestCase {
    function testImageFeed() {
        $this->get(TEST_BASE.'/rss/images');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");
    }
}
?>
