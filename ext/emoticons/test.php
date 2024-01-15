<?php

declare(strict_types=1);

namespace Shimmie2;

class EmoticonsTest extends ShimmiePHPUnitTestCase
{
    public function testEmoticons(): void
    {
        global $user;

        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

        send_event(new CommentPostingEvent($image_id, $user, ":cool: :beans:"));

        $this->get_page("post/view/$image_id");
        $this->assert_no_text(":cool:"); # FIXME: test for working image link
        //$this->assert_text(":beans:"); # FIXME: this should be left as-is

        $this->get_page("emote/list");
        //$this->assert_text(":arrow:");
    }
}
