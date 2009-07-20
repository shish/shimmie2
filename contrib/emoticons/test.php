<?php
class EmoticonTest extends ShimmieWebTestCase {
	function testEmoticons() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get_page("post/view/$image_id");

		$this->setField('comment', ":cool: :beans:");
		$this->click("Post Comment");
		$this->assertNoText(":cool:"); # FIXME: test for working image link
		#$this->assertText(":beans:"); # FIXME: this should be left as-is

		$this->log_out();
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
