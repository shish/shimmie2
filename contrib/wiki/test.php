<?php
class WikiTest extends ShimmieWebTestCase {
	function testWiki() {
		$this->log_in_as_admin();
		$this->get_page("wiki");
		$this->assertTitle("Index");
		$this->log_out();

		# FIXME: needs a ton of tests...
	}
}
?>
