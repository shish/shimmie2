<?php
class AliasEditorTest extends ShimmieWebTestCase {
	function testAliasEditor() {
        $this->get_page('alias/list');
		$this->assertTitle("Alias List");

		$this->log_in_as_admin();
        $this->get_page('alias/list');
		$this->assertTitle("Alias List");
		$this->log_out();
	}
}
?>
