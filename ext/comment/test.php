<?php
class CommentListTest extends WebTestCase {
	function testCommentsPage() {
        $this->get(TEST_BASE.'/comment/list');
        $this->assertTitle('Comments');

        $this->get(TEST_BASE.'/comment/list/2');
        $this->assertTitle('Comments');
	}
}
?>
