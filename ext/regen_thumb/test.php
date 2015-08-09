<?php
class RegenThumbTest {
	function testRegenThumb() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get_page("post/view/$image_id");
		$this->click("Regenerate");
		$this->assert_title("Thumbnail Regenerated");
		$this->delete_image($image_id);
		$this->log_out();

		# FIXME: test that the thumb's modified time has been updated
	}
}

