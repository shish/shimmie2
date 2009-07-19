<?php
class BulkAddTest extends ShimmieWebTestCase {
	function testBulkAdd() {
		$this->log_in_as_admin();

        $this->get_page('admin');
		$this->assertTitle("Admin Tools");
		$this->setField('dir', "contrib/simpletest");
		$this->click("Add");

		$this->get_page("post/list/hash=17fc89f372ed3636e28bd25cc7f3bac1/1");
		$this->assertTitle(new PatternExpectation("/^Image \d+: data/"));
		$this->click("Delete");

		$this->get_page("post/list/hash=feb01bab5698a11dd87416724c7a89e3/1");
		$this->assertTitle(new PatternExpectation("/^Image \d+: data/"));
		$this->click("Delete");

		$this->log_out();
	}
}
?>
