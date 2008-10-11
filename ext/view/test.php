<?php
class ViewTest extends WebTestCase {
	function testViewPage() {
        $this->get('http://shimmie.shishnet.org/v2/post/view/1914');
        $this->assertTitle('Image 1914: test');

        $this->get('http://shimmie.shishnet.org/v2/post/view/1');
        $this->assertTitle('Image not found');

        $this->get('http://shimmie.shishnet.org/v2/post/view/-1');
        $this->assertTitle('Image not found');
	}
}
?>
