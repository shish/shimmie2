<?php
class RegenThumbTest extends ShimmieWebTestCase {
	function testRegenThumb() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->get_page("post/view/$image_id");
		$this->click("Regenerate");
		$this->assertTitle("Thumbnail Regenerated");
		$this->delete_image($image_id);
		$this->log_out();

		# FIXME: test that the thumb's modified time has been updated
	}
}
?>
