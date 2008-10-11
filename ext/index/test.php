<?php
class IndexTest extends WebTestCase {
	function testIndexPage() {
        $this->get('http://shimmie.shishnet.org/v2/post/list');
		$this->assertTitle("Shimmie Testbed");
		$this->assertText("Prev | Index | Next");

        $this->get('http://shimmie.shishnet.org/v2/post/list/-1');
		$this->assertTitle("Shimmie Testbed");

        $this->get('http://shimmie.shishnet.org/v2/post/list/0');
		$this->assertTitle("Shimmie Testbed");

        $this->get('http://shimmie.shishnet.org/v2/post/list/1');
		$this->assertTitle("Shimmie Testbed");

        $this->get('http://shimmie.shishnet.org/v2/post/list/99999');
		$this->assertTitle("Shimmie Testbed");
	}

	function testSearches() {
        $this->get('http://shimmie.shishnet.org/v2/post/list/maumaumau/1');
		$this->assertTitle("maumaumau");
		$this->assertText("No Images Found");

        $this->get('http://shimmie.shishnet.org/v2/post/list/screenshot/1');
		$this->assertTitle("screenshot");

		$this->get('http://shimmie.shishnet.org/v2/post/list/screenshot%20computer/1');
		$this->assertTitle("screenshot computer");

		$this->get('http://shimmie.shishnet.org/v2/post/list/screenshot%20computer%20-pbx/1');
		$this->assertTitle("screenshot computer -pbx");
	}
}
?>
