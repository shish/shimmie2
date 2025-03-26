<?php

declare(strict_types=1);

namespace Shimmie2;

final class EmoticonsTest extends ShimmiePHPUnitTestCase
{
    public function testEmoticons(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        send_event(new CommentPostingEvent($image_id, Ctx::$user, ":cool: :beans:"));

        self::get_page("post/view/$image_id");
        self::assert_no_text(":cool:"); # FIXME: test for working image link
        //self::assert_text(":beans:"); # FIXME: this should be left as-is

        self::get_page("emote/list");
        //self::assert_text(":arrow:");
    }
}
