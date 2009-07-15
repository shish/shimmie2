<?php
class CommentListTest extends ShimmieWebTestCase {
	function testCommentsPage() {
        $this->get_page('comment/list');
        $this->assertTitle('Comments');

        $this->get_page('comment/list/2');
        $this->assertTitle('Comments');
	}
}
?>
