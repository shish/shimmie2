<?php
class ExtManagerTest extends SCoreWebTestCase {
	function testAuth() {
        $this->get_page('ext_manager');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");

		$this->log_in_as_user();
        $this->get_page('ext_manager');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");
		$this->log_out();

		$this->log_in_as_admin();
        $this->get_page('ext_manager');
		$this->assert_title("Extensions");
		$this->assert_text("SimpleTest integration");
		$this->log_out();

		# FIXME: test that some extensions can be added and removed? :S
	}
}
?>
