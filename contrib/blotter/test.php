<?php
class BlotterTest extends SCoreWebTestCase {
	function testView() {
		$this->log_in_as_admin();
		$this->assert_text("Blotter Editor");
		$this->click("Blotter Editor");
		$this->log_out();
	}

	function testAdd() {
		$this->log_in_as_admin();
		$this->get_page("blotter/editor");
		$this->set_field("entry_text", "blotter testing");
		$this->click("Add");
		$this->assert_text("blotter testing");
		$this->clock("Remove");
		$this->assert_no_text("blotter testing");
		$this->log_out();
	}
}
?>
