<?php
class EmoticonTest extends ShimmieWebTestCase {
	function testEmoticons() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get_page("post/view/$image_id");

		$this->set_field('comment', ":cool: :beans:");
		$this->click("Post Comment");
		$this->assert_no_text(":cool:"); # FIXME: test for working image link
		#$this->assert_text(":beans:"); # FIXME: this should be left as-is

		$this->get_page("emote/list");
		$this->assert_text(":arrow:");

		$this->log_out();
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}

