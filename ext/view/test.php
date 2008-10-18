<?php
class ViewTest extends WebTestCase {
	function testViewPage() {
        $this->get(TEST_BASE.'/post/view/1914');
        $this->assertTitle('Image 1914: test');

        $this->get(TEST_BASE.'/post/view/1');
        $this->assertTitle('Image not found');

        $this->get(TEST_BASE.'/post/view/-1');
        $this->assertTitle('Image not found');
	}
}
?>
