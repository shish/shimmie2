<?php
class CommentListTest extends ShimmieWebTestCase {
	function setUp() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_comment_limit", "100");
		$this->click("Save Settings");
		$this->log_out();
	}

	function tearDown() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_comment_limit", "10");
		$this->click("Save Settings");
		$this->log_out();
	}

	function testCommentsPage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");

		# a good comment
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "Test Comment ASDFASDF");
		$this->click("Post Comment");
		$this->assert_text("ASDFASDF");

		# dupe
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "Test Comment ASDFASDF");
		$this->click("Post Comment");
		$this->assert_text("try and be more original");

		# empty comment
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "");
		$this->click("Post Comment");
		$this->assert_text("Comments need text...");

		# whitespace is still empty...
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', " \t\r\n");
		$this->click("Post Comment");
		$this->assert_text("Comments need text...");

		# repetitive (aka. gzip gives >= 10x improvement)
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', str_repeat("U", 5000));
		$this->click("Post Comment");
		$this->assert_text("Comment too repetitive~");

		# test UTF8
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "Test Comment むちむち");
		$this->click("Post Comment");
		$this->assert_text("むちむち");

		# test that search by comment metadata works
		$this->get_page("post/list/commented_by=test/1");
		$this->assert_title("Image $image_id: pbx");
		$this->get_page("post/list/comments=2/1");
		$this->assert_title("Image $image_id: pbx");

		$this->log_out();

		$this->get_page('comment/list');
		$this->assert_title('Comments');
		$this->assert_text('ASDFASDF');

		$this->get_page('comment/list/2');
		$this->assert_title('Comments');

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();

		$this->get_page('comment/list');
		$this->assert_title('Comments');
		$this->assert_no_text('ASDFASDF');
	}

	function testSingleDel() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");

		# make a comment
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "Test Comment ASDFASDF");
		$this->click("Post Comment");
		$this->assert_title("Image $image_id: pbx");
		$this->assert_text("ASDFASDF");

		# delete it
		$this->click("Del");
		$this->assert_title("Image $image_id: pbx");
		$this->assert_no_text("ASDFASDF");

		# tidy up
		$this->delete_image($image_id);
		$this->log_out();
	}
}

