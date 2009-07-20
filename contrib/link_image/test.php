<?php
class LinkImageTest extends ShimmieWebTestCase {
	function testLinkImage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pie");

		# look in the "plain text link to post" box, follow the link
		# in there, see if it takes us to the right page
		$raw = $this->get_page("post/view/$image_id");
		$matches = preg_match("/name='text_post-link'\s+value='([^']+)'/", $raw);
		$this->assertTrue(count($matches) > 0);
		$this->get($matches[1]);
		$this->assertTitle("Image $image_id: pie");

		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
