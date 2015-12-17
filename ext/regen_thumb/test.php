<?php
class RegenThumbTest extends ShimmiePHPUnitTestCase {
	public function testRegenThumb() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get_page("post/view/$image_id");

		$_POST['image_id'] = $image_id;
		$this->get_page("regen_thumb");
		$this->assert_title("Thumbnail Regenerated");

		# FIXME: test that the thumb's modified time has been updated
	}
}

