<?php
class PMTest extends SCoreWebTestCase {
	function testPM() {
		$this->log_in_as_admin();
		$this->get_page("user/test");
		$this->setField('subject', "message demo to test");
		$this->setField('message', "message contents");
		$this->click("Send");
		$this->log_out();

		$this->log_in_as_user();
		$this->get_page("user");
		$this->assertText("message demo to test");
		$this->click("message demo to test");
		$this->assertText("message contents");
		$this->back();
		$this->click("Delete");
		$this->assertNoText("message demo to test");
		$this->log_out();
	}

	function testAdminAccess() {
		$this->log_in_as_admin();
		$this->get_page("user/test");
		$this->setField('subject', "message demo to test");
		$this->setField('message', "message contents");
		$this->click("Send");

		$this->get_page("user/test");
		$this->assertText("message demo to test");
		$this->click("message demo to test");
		$this->assertText("message contents");
		$this->back();
		$this->click("Delete");

		# simpletest bug? - redirect(referrer) works in opera, not in
		# webtestcase, so we end up at the wrong page...
		$this->get_page("user/test");
		$this->assertTitle("test's Page");
		$this->assertNoText("message demo to test");
		$this->log_out();
	}
}
?>
