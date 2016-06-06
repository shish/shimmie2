<?php
class IndexTest extends ShimmiePHPUnitTestCase {
	private function upload() {
		$this->log_in_as_user();
		$image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "thing computer screenshot pbx phone");
		$image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "thing computer computing bedroom workshop");
		$this->log_out();

		# make sure both uploads were ok
		$this->assertTrue($image_id_1 > 0);
		$this->assertTrue($image_id_2 > 0);

		return array($image_id_1, $image_id_2);
	}

	public function testIndexPage() {
		$this->get_page('post/list');
		$this->assert_title("Welcome to Shimmie ".VERSION);
		$this->assert_no_text("Prev | Index | Next");

		$this->log_in_as_user();
		$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->log_out();

		$this->get_page('post/list');
		$this->assert_title("Shimmie");
		// FIXME
		//$this->assert_text("Prev | Index | Next");

		$this->get_page('post/list/-1');
		$this->assert_title("Shimmie");

		$this->get_page('post/list/0');
		$this->assert_title("Shimmie");

		$this->get_page('post/list/1');
		$this->assert_title("Shimmie");

		$this->get_page('post/list/99999');
		$this->assert_response(404);
	}

	/* * * * * * * * * * *
	* Tag Search         *
	* * * * * * * * * * */
	public function testTagSearchNoResults() {
		$image_ids = $this->upload();

		$this->get_page('post/list/maumaumau/1');
		$this->assert_response(404);
	}

	public function testTagSearchOneResult() {
		$image_ids = $this->upload();

		$this->get_page("post/list/pbx/1");
		$this->assert_response(302);
	}

	public function testTagSearchManyResults() {
		$image_ids = $this->upload();

		$this->get_page('post/list/computer/1');
		$this->assert_response(200);
		$this->assert_title("computer");
	}

	/* * * * * * * * * * *
	* Multi-Tag Search   *
	* * * * * * * * * * */
	public function testMultiTagSearchNoResults() {
		$image_ids = $this->upload();

		# multiple tags, one of which doesn't exist
		# (test the "one tag doesn't exist = no hits" path)
		$this->get_page('post/list/computer asdfasdfwaffle/1');
		$this->assert_response(404);
	}

	public function testMultiTagSearchOneResult() {
		$image_ids = $this->upload();

		$this->get_page('post/list/computer screenshot/1');
		$this->assert_response(302);
	}

	public function testMultiTagSearchManyResults() {
		$image_ids = $this->upload();

		$this->get_page('post/list/computer thing/1');
		$this->assert_response(200);
	}

	/* * * * * * * * * * *
	* Meta Search        *
	* * * * * * * * * * */
	public function testMetaSearchNoResults() {
		$this->get_page('post/list/hash=1234567890/1');
		$this->assert_response(404);
	}

	public function testMetaSearchOneResult() {
		$image_ids = $this->upload();

		$this->get_page("post/list/hash=feb01bab5698a11dd87416724c7a89e3/1");
		$this->assert_response(302);

		$this->get_page("post/list/md5=feb01bab5698a11dd87416724c7a89e3/1");
		$this->assert_response(302);

		$this->get_page("post/list/id={$image_ids[1]}/1");
		$this->assert_response(302);

		$this->get_page("post/list/filename=screenshot/1");
		$this->assert_response(302);

	}

	public function testMetaSearchManyResults() {
		$image_ids = $this->upload();

		$this->get_page('post/list/size=640x480/1');
		$this->assert_response(200);

		$this->get_page("post/list/tags=5/1");
		$this->assert_response(200);

		$this->get_page("post/list/ext=jpg/1");
		$this->assert_response(200);
	}

	/* * * * * * * * * * *
	* Wildcards          *
	* * * * * * * * * * */
	public function testWildSearch() {
		$image_ids = $this->upload();

		// Only the first image matches both the wildcard and the tag.
		// This checks for https://github.com/shish/shimmie2/issues/547
		// (comp* is expanded to "computer computing", then we searched
		// for images which match two or more of the tags in
		// "computer computing screenshot")
		$this->get_page("post/list/comp* screenshot/1");
		$this->assert_response(302);
	}
    
	/* * * * * * * * * * *
	* Mixed              *
	* * * * * * * * * * */
	public function testMixedSearchTagMeta() {
		$image_ids = $this->upload();

		# multiple tags, many results
		$this->get_page('post/list/computer size=640x480/1');
		$this->assert_response(200);
	}
	// tag + negative
	// wildcards + ???

	/* * * * * * * * * * *
	* Other              *
	* - negative tags    *
	* - wildcards        *
	* * * * * * * * * * */
	public function testOther() {
		$this->markTestIncomplete();

		# negative tag, should have one result
		$this->get_page('post/list/computer -pbx/1');
		$this->assert_response(302);

		# negative tag alone, should work
		# FIXME: known broken in mysql
		//$this->get_page('post/list/-pbx/1');
		//$this->assert_response(302);

		# test various search methods
		$this->get_page("post/list/bedroo*/1");
		$this->assert_response(302);
	}
}

