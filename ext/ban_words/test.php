<?php

declare(strict_types=1);

namespace Shimmie2;

class BanWordsTest extends ShimmiePHPUnitTestCase
{
    public function check_blocked(int $image_id, string $words): void
    {
        global $user;
        try {
            send_event(new CommentPostingEvent($image_id, $user, $words));
            $this->fail("Exception not thrown");
        } catch (CommentPostingException $e) {
            $this->assertEquals("Comment contains banned terms", $e->getMessage());
        }
    }

    public function testWordBan(): void
    {
        global $config;
        $config->set_string("banned_words", "viagra\nporn\n\n/http:.*\.cn\//");

        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        $this->check_blocked($image_id, "kittens and viagra");
        $this->check_blocked($image_id, "kittens and ViagrA");
        $this->check_blocked($image_id, "kittens and viagra!");
        $this->check_blocked($image_id, "some link to http://something.cn/");

        $this->get_page('comment/list');
        $this->assert_title('Comments');
        $this->assert_no_text('viagra');
        $this->assert_no_text('ViagrA');
        $this->assert_no_text('http://something.cn/');
    }
}
