<?php declare(strict_types=1);
class LinkImageTest extends ShimmiePHPUnitTestCase
{
    public function testLinkImage()
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pie");
        $this->get_page("post/view/$image_id");

        $matches = [];
        preg_match("#value='https?://.*/(post/view/[0-9]+)'#", $this->page_to_text(), $matches);
        $this->assertTrue(count($matches) > 0);
        $page = $this->get_page($matches[1]);
        $this->assertEquals("Post $image_id: pie", $page->title);
    }
}
