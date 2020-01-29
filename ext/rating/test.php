<?php declare(strict_types=1);
class RatingsTest extends ShimmiePHPUnitTestCase
{
    public function testRatingSafe()
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id($image_id);
        send_event(new RatingSetEvent($image, "s"));

        # search for it in various ways
        $page = $this->get_page("post/list/rating=Safe/1");
        $this->assertEquals("/post/view/1", $page->redirect);

        $page = $this->get_page("post/list/rating=s/1");
        $this->assertEquals("/post/view/1", $page->redirect);

        $page = $this->get_page("post/list/rating=sqe/1");
        $this->assertEquals("/post/view/1", $page->redirect);

        # test that search by tag still works
        $page = $this->get_page("post/list/pbx/1");
        $this->assertEquals("/post/view/1", $page->redirect);

        # searching for a different rating should return nothing
        $page = $this->get_page("post/list/rating=q/1");
        $this->assertEquals("No Images Found", $page->heading);
    }

    public function testRatingExplicit()
    {
        global $config;
        $config->set_array("ext_rating_anonymous_privs", ["s", "q"]);
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id($image_id);
        send_event(new RatingSetEvent($image, "e"));

        # the explicit image shouldn't show up in anon's searches
        $this->log_out();
        $page = $this->get_page("post/list/pbx/1");
        $this->assertEquals("No Images Found", $page->heading);
    }
}
