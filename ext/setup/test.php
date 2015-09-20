<?php
class SetupTest extends ShimmiePHPUnitTestCase {
	function testNiceUrlsTest() {
		# XXX: this only checks that the text is "ok", to check
		# for a bug where it was coming out as "\nok"; it doesn't
		# check that niceurls actually work
		$this->get_page('nicetest');
		$this->assert_content("ok");
		$this->assert_no_content("\n");
	}

	function testAuthAnon() {
		$this->get_page('setup');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");
	}

	function testAuthUser() {
		$this->log_in_as_user();
		$this->get_page('setup');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");
	}

	function testAuthAdmin() {
		$this->log_in_as_admin();
		$this->get_page('setup');
		$this->assert_title("Shimmie Setup");
		$this->assert_text("General");
	}

	function testAdvanced() {
		$this->log_in_as_admin();
		$this->get_page('setup/advanced');
		$this->assert_title("Shimmie Setup");
		$this->assert_text("thumb_quality");
	}
}

