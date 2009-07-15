<?php
class ViewTest extends ShimmieWebTestCase {
	function testViewPage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "test");
		$idp1 = $image_id + 1;
		$this->log_out();

        $this->get_page("post/view/$image_id");
        $this->assertTitle("Image $image_id: test");

        $this->get_page("post/view/$idp1");
        $this->assertTitle('Image not found');

        $this->get_page('post/view/-1');
        $this->assertTitle('Image not found');

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
