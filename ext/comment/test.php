<?php
class CommentListTest extends ShimmieWebTestCase {
	function testCommentsPage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");

		# a good comment
		$this->get_page("post/view/$image_id");
		$this->setField('comment', "Test Comment ASDFASDF");
		$this->click("Post Comment");
		$this->assertText("ASDFASDF");

		# dupe
		$this->get_page("post/view/$image_id");
		$this->setField('comment', "Test Comment ASDFASDF");
		$this->click("Post Comment");
		$this->assertText("try and be more original");

		# empty comment
		$this->get_page("post/view/$image_id");
		$this->setField('comment', "");
		$this->click("Post Comment");
		$this->assertText("Comments need text...");

		# whitespace is still empty...
		$this->get_page("post/view/$image_id");
		$this->setField('comment', " \t\r\n");
		$this->click("Post Comment");
		$this->assertText("Comments need text...");

		# repetitive (aka. gzip gives >= 10x improvement)
		$this->get_page("post/view/$image_id");
		$this->setField('comment', str_repeat("U", 5000));
		$this->click("Post Comment");
		$this->assertText("Comment too repetitive~");

		# test that search by comment metadata works
		$this->get_page("post/list/commented_by=test/1");
		$this->assertTitle("Image $image_id: pbx");
		$this->get_page("post/list/comments=1/1");
		$this->assertTitle("Image $image_id: pbx");

		$this->log_out();

		$this->get_page('comment/list');
		$this->assertTitle('Comments');
		$this->assertText('ASDFASDF');

		$this->get_page('comment/list/2');
		$this->assertTitle('Comments');

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	function testSingleDel() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");

		# make a comment
		$this->get_page("post/view/$image_id");
		$this->setField('comment', "Test Comment ASDFASDF");
		$this->click("Post Comment");
		$this->assertTitle("Image $image_id: pbx");
		$this->assertText("ASDFASDF");

		# delete it
		$this->click("Del");
		$this->assertTitle("Image $image_id: pbx");
		$this->assertNoText("ASDFASDF");

		# tidy up
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
