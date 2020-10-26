<?php declare(strict_types=1);
class FeaturedTest extends ShimmiePHPUnitTestCase
{
    public function testFeatured()
    {
        global $config;

        // Set up
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        # FIXME: test that regular users can't feature things

        // Admin can feature things
        // FIXME: use Event rather than modifying database
        // $this->log_in_as_admin();
        // send_event(new SetFeaturedEvent($image_id));
        $config->set_int("featured_id", $image_id);

        $this->get_page("post/list");
        $this->assert_text("Featured Post");

        # FIXME: test changing from one feature to another

        $page = $this->get_page("featured_image/download");
        $this->assertEquals(200, $page->code);

        $page = $this->get_page("featured_image/view");
        $this->assertEquals(200, $page->code);

        // after deletion, there should be no feature
        $this->delete_image($image_id);
        $this->get_page("post/list");
        $this->assert_no_text("Featured Post");
    }
}
