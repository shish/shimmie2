<?php

declare(strict_types=1);

namespace Shimmie2;

final class BanWordsTest extends ShimmiePHPUnitTestCase
{
    public function check_blocked(int $image_id, string $words): void
    {
        global $user;
        try {
            send_event(new CommentPostingEvent($image_id, $user, $words));
            self::fail("Exception not thrown");
        } catch (CommentPostingException $e) {
            self::assertEquals("Comment contains banned terms", $e->getMessage());
        }
    }

    public function testWordBan(): void
    {
        global $config;
        $config->set_string("banned_words", "viagra\nporn\n\n/http:.*\.cn\//");

        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        $this->check_blocked($image_id, "kittens and viagra");
        $this->check_blocked($image_id, "kittens and ViagrA");
        $this->check_blocked($image_id, "kittens and viagra!");
        $this->check_blocked($image_id, "some link to http://something.cn/");

        self::get_page('comment/list');
        self::assert_title('Comments');
        self::assert_no_text('viagra');
        self::assert_no_text('ViagrA');
        self::assert_no_text('http://something.cn/');
    }

    public function testCyrillicBan(): void
    {
        global $config;
        $config->set_string("banned_words", "СОЮЗ\nсоветских\nСоциалистических\n/Республик/\n");

        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        // check lowercase ban matches uppercase word
        $this->check_blocked($image_id, "СОВЕТСКИХ");
        // check uppercase regex-ban matches lowercase word
        $this->check_blocked($image_id, "республик");

        self::get_page('comment/list');
        self::assert_title('Comments');
        self::assert_no_text('СОВЕТСКИХ');
        self::assert_no_text('республик');
    }

}
