<?php
class FavoritesTest extends ShimmieWebTestCase {
	function testFavorites() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "test");

		$this->get_page("post/view/$image_id");
		$this->assertTitle("Image $image_id: test");
		$this->assertNoText("Favorited By");

		$this->click("Favorite");
		$this->assertText("Favorited By");

		$this->get_page("post/list/favorited_by=test/1");
		$this->assertTitle("Image $image_id: test");
		$this->assertText("Favorited By");

		$this->get_page("user/test");
		$this->assertText("Images favorited: 1");
		$this->click("Images favorited");
		$this->assertTitle("Image $image_id: test");

		$this->click("Un-Favorite");
		$this->assertNoText("Favorited By");

		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
