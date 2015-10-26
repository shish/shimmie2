<?php
class DanbooruApiTest extends ShimmiePHPUnitTestCase {
	public function testSearch() {
		$this->log_in_as_admin();

		$image_id = $this->post_image("tests/bedroom_workshop.jpg", "data");

		$this->get_page("api/danbooru/find_posts");
		$this->get_page("api/danbooru/find_posts?id=$image_id");
		$this->get_page("api/danbooru/find_posts?md5=17fc89f372ed3636e28bd25cc7f3bac1");

		$this->get_page("api/danbooru/find_tags");
		$this->get_page("api/danbooru/find_tags?id=1");
		$this->get_page("api/danbooru/find_tags?name=data");

		$this->get_page("api/danbooru/post/show/$image_id");
		//$this->assert_response(302);  // FIXME

		$this->get_page("post/list/md5:17fc89f372ed3636e28bd25cc7f3bac1/1");
		//$this->assert_title(new PatternExpectation("/^Image \d+: data/"));
		//$this->click("Delete");
	}
}
