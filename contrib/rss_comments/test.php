<?php
class RSSCommentsTest extends ShimmieWebTestCase {
    function testImageFeed() {
        $this->get_page('rss/comments');
		$this->assert_mime("application/rss+xml");
		$this->assert_no_text("Exception");

		# FIXME: test that there are some comments here
    }
}
?>
