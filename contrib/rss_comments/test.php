<?php
class RSSCommentsTest extends WebTestCase {
    function testImageFeed() {
        $this->get('http://shimmie.shishnet.org/v2/rss/comments');
		$this->assertMime("application/rss+xml");
		$this->assertNoText("Exception");
    }
}
?>
