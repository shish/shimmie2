<?php
class IndexTest extends ShimmieWebTestCase {
	function testIndexPage() {
		$this->get_page('post/list');
		$this->assert_title("Welcome to Shimmie ".VERSION);
		$this->assert_no_text("Prev | Index | Next");

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->log_out();

        $this->get_page('post/list');
		$this->assert_title("Shimmie");
		$this->assert_text("Prev | Index | Next");

        $this->get_page('post/list/-1');
		$this->assert_title("Shimmie");

        $this->get_page('post/list/0');
		$this->assert_title("Shimmie");

        $this->get_page('post/list/1');
		$this->assert_title("Shimmie");

        $this->get_page('post/list/99999');
		$this->assert_title("Shimmie");

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();

		# FIXME: test search box
	}

	function testSearches() {
		$this->log_in_as_user();
		$image_id_1 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$image_id_2 = $this->post_image("ext/simpletest/data/bedroom_workshop.jpg", "computer bedroom workshop");
		$this->log_out();

		# make sure both uploads were ok
		$this->assertTrue($image_id_1 > 0);
		$this->assertTrue($image_id_2 > 0);

		# regular tag, no results
        $this->get_page('post/list/maumaumau/1');
		$this->assert_title("maumaumau");
		$this->assert_text("No Images Found");

		# regular tag, many results
        $this->get_page('post/list/computer/1');
		$this->assert_title("computer");
		$this->assert_no_text("No Images Found");

		# meta tag, many results
        $this->get_page('post/list/size=640x480/1');
		$this->assert_title("size=640x480");
		$this->assert_no_text("No Images Found");

		# meta tag, one result
		$this->get_page("post/list/hash=feb01bab5698a11dd87416724c7a89e3/1");
		$this->assert_title(new PatternExpectation("/^Image $image_id_1: /"));
		$this->assert_no_text("No Images Found");

		# meta tag, one result
		$this->get_page("post/list/md5=feb01bab5698a11dd87416724c7a89e3/1");
		$this->assert_title(new PatternExpectation("/^Image $image_id_1: /"));
		$this->assert_no_text("No Images Found");

		# multiple tags, many results
        $this->get_page('post/list/computer%20size=640x480/1');
		$this->assert_title("computer size=640x480");
		$this->assert_no_text("No Images Found");

		# multiple tags, single result; search with one result = direct to image
		$this->get_page('post/list/screenshot%20computer/1');
		$this->assert_title(new PatternExpectation("/^Image $image_id_1: /"));

		# negative tag, should have one result
		$this->get_page('post/list/computer%20-pbx/1');
		$this->assert_title(new PatternExpectation("/^Image $image_id_2: /"));

		# negative tag alone, should work
		# FIXME: known broken in mysql
		//$this->get_page('post/list/-pbx/1');
		//$this->assert_title(new PatternExpectation("/^Image $image_id_2: /"));

		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->log_out();
	}
}
?>
