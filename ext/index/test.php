<?php
class IndexTest extends WebTestCase {
	function testIndexPage() {
        $this->get(TEST_BASE.'/post/list');
		$this->assertTitle("Shimmie Testbed");
		$this->assertText("Prev | Index | Next");

        $this->get(TEST_BASE.'/post/list/-1');
		$this->assertTitle("Shimmie Testbed");

        $this->get(TEST_BASE.'/post/list/0');
		$this->assertTitle("Shimmie Testbed");

        $this->get(TEST_BASE.'/post/list/1');
		$this->assertTitle("Shimmie Testbed");

        $this->get(TEST_BASE.'/post/list/99999');
		$this->assertTitle("Shimmie Testbed");
	}

	function testSearches() {
        $this->get(TEST_BASE.'/post/list/maumaumau/1');
		$this->assertTitle("maumaumau");
		$this->assertText("No Images Found");

        $this->get(TEST_BASE.'/post/list/screenshot/1');
		$this->assertTitle("screenshot");

        $this->get(TEST_BASE.'/post/list/size=1024x768/1');
		$this->assertTitle("size=1024x768");

        $this->get(TEST_BASE.'/post/list/screenshot%20size=1024x768/1');
		$this->assertTitle("screenshot size=1024x768");

		$this->get(TEST_BASE.'/post/list/screenshot%20computer/1');
		$this->assertTitle("screenshot computer");

		$this->get(TEST_BASE.'/post/list/screenshot%20computer%20-pbx/1');
		$this->assertTitle("screenshot computer -pbx");
	}
}
?>
