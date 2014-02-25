<?php
class ViewTest extends ShimmieWebTestCase {
	function testViewPage() {
		$this->log_in_as_user();
		$image_id_1 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "test");
		$image_id_2 = $this->post_image("ext/simpletest/data/bedroom_workshop.jpg", "test2");
		$image_id_3 = $this->post_image("ext/simpletest/data/favicon.png", "test");
		$idp1 = $image_id_3 + 1;
		$this->log_out();

		$this->get_page("post/view/$image_id_1");
		$this->assert_title("Image $image_id_1: test");

		$this->click("Prev");
		$this->assert_title("Image $image_id_2: test2");

		$this->click("Next");
		$this->assert_title("Image $image_id_1: test");

		$this->click("Next");
		$this->assert_title("Image not found");

		$this->get_page("post/view/$idp1");
		$this->assert_title('Image not found');

		$this->get_page('post/view/-1');
		$this->assert_title('Image not found');

		/*
		 * FIXME: this assumes Nice URLs.
		 *
		# note: skips image #2
		$this->get_page("post/view/$image_id_1?search=test"); // FIXME: assumes niceurls
		$this->click("Prev");
		$this->assert_title("Image $image_id_3: test");
		*/
	
		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->delete_image($image_id_3);
		$this->log_out();
	}
}
?>
