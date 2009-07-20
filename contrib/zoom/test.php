<?php
class ZoomTest extends ShimmieWebTestCase {
	function testZoom() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get("post/view/$image_id"); # just check that the page isn't borked
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
