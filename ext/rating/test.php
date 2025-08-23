<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsTest extends ShimmiePHPUnitTestCase
{
    public function testRatingSafe(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);
        send_event(new RatingSetEvent($image, "s"));

        # search for it in various ways
        self::assert_search_results(["rating=Safe"], [$image_id]);
        self::assert_search_results(["rating=s"], [$image_id]);
        self::assert_search_results(["rating=sqe"], [$image_id]);

        # test that search by tag still works
        self::assert_search_results(["pbx"], [$image_id]);

        # searching for a different rating should return nothing
        self::assert_search_results(["rating=q"], []);
    }

    public function testRatingExplicit(): void
    {
        Ctx::$config->set("ext_rating_anonymous_privs", ["s", "q"]);
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);
        send_event(new RatingSetEvent($image, "e"));

        # the explicit image shouldn't show up in anon's searches
        self::log_out();
        self::assert_search_results(["pbx"], []);
    }

    public function testUserConfig(): void
    {
        // post a safe image and an explicit image
        self::log_in_as_user();
        $image_id_e = $this->post_image("tests/bedroom_workshop.jpg", "pbx");
        $image_e = Image::by_id_ex($image_id_e);
        send_event(new RatingSetEvent($image_e, "e"));
        $image_id_s = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_s = Image::by_id_ex($image_id_s);
        send_event(new RatingSetEvent($image_s, "s"));

        // user is allowed to see all
        Ctx::$config->set("ext_rating_user_privs", ["s", "q", "e"]);

        // user prefers safe-only by default
        Ctx::$user->get_config()->set(RatingsUserConfig::DEFAULTS, ["s"]);

        // search with no tags should return only safe image
        self::assert_search_results([], [$image_id_s]);

        // specifying a rating should return only that rating
        self::assert_search_results(["rating=e"], [$image_id_e]);
        self::assert_search_results(["rating=s"], [$image_id_s]);

        // If user prefers to see all images, going to the safe image
        // and clicking next should show the explicit image
        Ctx::$user->get_config()->set(RatingsUserConfig::DEFAULTS, ["s", "q", "e"]);
        $next = $image_s->get_next();
        self::assertNotNull($next);
        self::assertEquals($next->id, $image_id_e);

        // If the user prefers to see only safe images by default, then
        // going to the safe image and clicking next should not show
        // the explicit image (See bug #984)
        Ctx::$user->get_config()->set(RatingsUserConfig::DEFAULTS, ["s"]);
        self::assertEquals($image_s->get_next(), null);
    }

    public function testCountImages(): void
    {
        self::log_in_as_user();

        $image_id_s = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_s = Image::by_id_ex($image_id_s);
        send_event(new RatingSetEvent($image_s, "s"));
        $image_id_q = $this->post_image("tests/favicon.png", "favicon");
        $image_q = Image::by_id_ex($image_id_q);
        send_event(new RatingSetEvent($image_q, "q"));
        $image_id_e = $this->post_image("tests/bedroom_workshop.jpg", "bedroom");
        $image_e = Image::by_id_ex($image_id_e);
        send_event(new RatingSetEvent($image_e, "e"));

        Ctx::$config->set("ext_rating_user_privs", ["s", "q"]);
        Ctx::$user->get_config()->set(RatingsUserConfig::DEFAULTS, ["s"]);

        self::assertEquals(1, Search::count_images(["rating=s"]), "UserClass has access to safe, show safe");
        self::assertEquals(2, Search::count_images(["rating=*"]), "UserClass has access to s/q - if user asks for everything, show those two but hide e");
        self::assertEquals(1, Search::count_images(), "If search doesn't specify anything, check the user defaults");
    }
}
