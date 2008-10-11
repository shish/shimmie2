<?php
class CommentListTest extends WebTestCase {
	function testCommentsPage() {
        $this->get('http://shimmie.shishnet.org/v2/comment/list');
        $this->assertTitle('Comments');

        $this->get('http://shimmie.shishnet.org/v2/comment/list/2');
        $this->assertTitle('Comments');
	}
}
?>
