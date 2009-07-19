<?php
class BulkAddTest extends ShimmieWebTestCase {
	function testBulkAdd() {
		$this->log_in_as_admin();

        $this->get_page('admin');
		$this->assertTitle("Admin Tools");
		$this->setField('dir', "contrib/simpletest");
		$this->click("Add");
		$this->delete_image($image_id);

		$this->log_out();
	}
}
?>
