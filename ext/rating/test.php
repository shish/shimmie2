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
        $this->assert_search_results(["rating=Safe"], [$image_id]);
        $this->assert_search_results(["rating=s"], [$image_id]);
        $this->assert_search_results(["rating=sqe"], [$image_id]);

        # test that search by tag still works
        $this->assert_search_results(["pbx"], [$image_id]);

        # searching for a different rating should return nothing
        $this->assert_search_results(["rating=q"], []);
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
        $this->assert_search_results(["pbx"], []);
    }
}
