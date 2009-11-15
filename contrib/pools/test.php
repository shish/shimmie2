<?php
class PoolsTest extends SCoreWebTestCase {
	function testPools() {
        $this->get_page('pool/list');
		$this->assert_title("Pools");
	}
}
?>
