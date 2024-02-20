<?php

declare(strict_types=1);

namespace Shimmie2;

class RatingsTest extends ShimmiePHPUnitTestCase
{
    public function testRatingSafe(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);
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

    public function testRatingExplicit(): void
    {
        global $config;
        $config->set_array("ext_rating_anonymous_privs", ["s", "q"]);
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);
        send_event(new RatingSetEvent($image, "e"));

        # the explicit image shouldn't show up in anon's searches
        $this->log_out();
        $this->assert_search_results(["pbx"], []);
    }

    public function testUserConfig(): void
    {
        global $config, $user_config;

        // post a safe image and an explicit image
        $this->log_in_as_user();
        $image_id_e = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $image_e = Image::by_id_ex($image_id_e);
        send_event(new RatingSetEvent($image_e, "e"));
        $image_id_s = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_s = Image::by_id_ex($image_id_s);
        send_event(new RatingSetEvent($image_s, "s"));

        // user is allowed to see all
        $config->set_array("ext_rating_user_privs", ["s", "q", "e"]);

        // user prefers safe-only by default
        $user_config->set_array(RatingsConfig::USER_DEFAULTS, ["s"]);

        // search with no tags should return only safe image
        $this->assert_search_results([], [$image_id_s]);

        // specifying a rating should return only that rating
        $this->assert_search_results(["rating=e"], [$image_id_e]);
        $this->assert_search_results(["rating=s"], [$image_id_s]);

        // If user prefers to see all images, going to the safe image
        // and clicking next should show the explicit image
        $user_config->set_array(RatingsConfig::USER_DEFAULTS, ["s", "q", "e"]);
        $this->assertEquals($image_s->get_next()->id, $image_id_e);

        // If the user prefers to see only safe images by default, then
        // going to the safe image and clicking next should not show
        // the explicit image (See bug #984)
        $user_config->set_array(RatingsConfig::USER_DEFAULTS, ["s"]);
        $this->assertEquals($image_s->get_next(), null);
    }

    public function testCountImages(): void
    {
        global $config, $user_config;

        $this->log_in_as_user();

        $image_id_s = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_s = Image::by_id_ex($image_id_s);
        send_event(new RatingSetEvent($image_s, "s"));
        $image_id_q = $this->post_image("tests/favicon.png", "favicon");
        $image_q = Image::by_id_ex($image_id_q);
        send_event(new RatingSetEvent($image_q, "q"));
        $image_id_e = $this->post_image("tests/bedroom_workshop.jpg", "bedroom");
        $image_e = Image::by_id_ex($image_id_e);
        send_event(new RatingSetEvent($image_e, "e"));

        $config->set_array("ext_rating_user_privs", ["s", "q"]);
        $user_config->set_array(RatingsConfig::USER_DEFAULTS, ["s"]);

        $this->assertEquals(1, Search::count_images(["rating=s"]), "UserClass has access to safe, show safe");
        $this->assertEquals(2, Search::count_images(["rating=*"]), "UserClass has access to s/q - if user asks for everything, show those two but hide e");
        $this->assertEquals(1, Search::count_images(), "If search doesn't specify anything, check the user defaults");
    }

    // reset the user config to defaults at the end of every test so
    // that it doesn't mess with other unrelated tests
    public function tearDown(): void
    {
        global $config, $user_config;
        $config->set_array("ext_rating_user_privs", ["?", "s", "q", "e"]);

        $this->log_in_as_user();
        $user_config->set_array(RatingsConfig::USER_DEFAULTS, ["?", "s", "q", "e"]);

        parent::tearDown();
    }
}
