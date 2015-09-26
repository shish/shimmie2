<?php
class LinkImageTest extends ShimmieWebTestCase {
	public function testLinkImage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pie");

		# look in the "plain text link to post" box, follow the link
		# in there, see if it takes us to the right page
		$raw = $this->get_page("post/view/$image_id");
		$matches = array();
		preg_match("#value='(http://.*(/|%2F)post(/|%2F)view(/|%2F)[0-9]+)'#", $raw, $matches);
		$this->assertTrue(count($matches) > 0);
		if($matches) {
			$this->get($matches[1]);
			$this->assert_title("Image $image_id: pie");
		}

		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}

