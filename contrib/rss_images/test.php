<?php
class RSSImagesTest extends WebTestCase {
    function testImageFeed() {
        $this->get(TEST_BASE.'/rss/images');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");

        $this->get(TEST_BASE.'/rss/images/1');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");

        $this->get(TEST_BASE.'/rss/images/tagme/1');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");

        $this->get(TEST_BASE.'/rss/images/tagme/2');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");
    }
}
?>
