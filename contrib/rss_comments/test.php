<?php
class RSSCommentsTest extends WebTestCase {
    function testImageFeed() {
        $this->get(TEST_BASE.'/rss/comments');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");
    }
}
?>
