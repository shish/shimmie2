<?php
class FavoritesTest extends ShimmiePHPUnitTestCase {
	public function testFavorites() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

		$this->get_page("post/view/$image_id");
		$this->assert_title("Image $image_id: test");
		$this->assert_no_text("Favorited By");

		$this->markTestIncomplete();

		$this->click("Favorite");
		$this->assert_text("Favorited By");

		$this->get_page("post/list/favorited_by=test/1");
		$this->assert_title("Image $image_id: test");
		$this->assert_text("Favorited By");

		$this->get_page("user/test");
		$this->assert_text("Images favorited: 1");
		$this->click("Images favorited");
		$this->assert_title("Image $image_id: test");

		$this->click("Un-Favorite");
		$this->assert_no_text("Favorited By");
	}
}

