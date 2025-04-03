<?php

declare(strict_types=1);

namespace Shimmie2;

final class RatingsBlurTest extends ShimmiePHPUnitTestCase
{
    private string $username = "test_ratings";

    public function testRatingBlurDefault(): void
    {
        self::log_in_as_user();
        $image_id_s = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_s = Image::by_id_ex($image_id_s);
        send_event(new RatingSetEvent($image_s, "s"));

        // the safe image should not insert a blur class
        self::get_page("post/list");
        self::assert_no_text("blur");

        $image_id_e = $this->post_image("tests/bedroom_workshop.jpg", "bedroom");
        $image_e = Image::by_id_ex($image_id_e);
        send_event(new RatingSetEvent($image_e, "e"));

        // the explicit image should insert a blur class
        self::get_page("post/list");
        self::assert_text("blur");
    }

    public function testRatingBlurGlobalConfig(): void
    {
        // change global setting: don't blur explict, only blur safe
        Ctx::$config->set(RatingsBlurConfig::GLOBAL_DEFAULTS, ["s"]);
        // create a new user to simulate inheriting the global default without manually setting the user default
        $this->create_test_user($this->username);

        $image_id_e = $this->post_image("tests/bedroom_workshop.jpg", "bedroom");
        $image_e = Image::by_id_ex($image_id_e);
        send_event(new RatingSetEvent($image_e, "e"));

        // the explicit image should not insert a blur class
        self::get_page("post/list");
        self::assert_no_text("blur");

        $image_id_s = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_s = Image::by_id_ex($image_id_s);
        send_event(new RatingSetEvent($image_s, "s"));

        // the safe image should insert a blur class
        self::get_page("post/list");
        self::assert_text("blur");

        // change global setting: don't blur any
        Ctx::$config->set(RatingsBlurConfig::GLOBAL_DEFAULTS, [RatingsBlur::NULL_OPTION]);
        // create a new user to simulate inheriting the global default without manually setting the user default
        $this->delete_test_user($this->username);
        $this->create_test_user($this->username);

        self::get_page("post/list");
        self::assert_no_text("blur");

        $this->delete_test_user($this->username);
    }

    public function testRatingBlurUserConfig(): void
    {
        // set global default to blur all, so we can test it is overriden
        Ctx::$config->set(RatingsBlurConfig::GLOBAL_DEFAULTS, array_keys(ImageRating::$known_ratings));

        self::log_in_as_user();

        // don't blur explict, blur safe
        Ctx::$user->get_config()->set(RatingsBlurUserConfig::USER_DEFAULTS, ["s"]);

        $image_id_e = $this->post_image("tests/bedroom_workshop.jpg", "bedroom");
        $image_e = Image::by_id_ex($image_id_e);
        send_event(new RatingSetEvent($image_e, "e"));

        // the explicit image should not insert a blur class
        self::get_page("post/list");
        self::assert_no_text("blur");

        $image_id_s = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image_s = Image::by_id_ex($image_id_s);
        send_event(new RatingSetEvent($image_s, "s"));

        // the safe image should insert a blur class
        self::get_page("post/list");
        self::assert_text("blur");

        // don't blur any
        Ctx::$user->get_config()->set(RatingsBlurUserConfig::USER_DEFAULTS, [RatingsBlur::NULL_OPTION]);

        self::get_page("post/list");
        self::assert_no_text("blur");
    }

    private function create_test_user(string $username): void
    {
        $uce = send_event(new UserCreationEvent($username, $username, $username, "$username@test.com", false));
        send_event(new UserLoginEvent($uce->get_user()));
    }

    private function delete_test_user(string $username): void
    {
        self::log_out();
        self::log_in_as_admin();
        send_event(new PageRequestEvent(
            "POST",
            "user_admin/delete_user",
            new QueryArray([]),
            new QueryArray(['id' => (string)User::by_name($username)->id, 'with_images' => '', 'with_comments' => ''])
        ));
        self::log_out();
    }

    // reset the user config to defaults at the end of every test so
    // that it doesn't mess with other unrelated tests
    public function tearDown(): void
    {
        self::log_in_as_user();
        Ctx::$user->get_config()->set(RatingsBlurUserConfig::USER_DEFAULTS, ['e']);
        parent::tearDown();
    }
}
