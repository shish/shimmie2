<?php

declare(strict_types=1);

namespace Shimmie2;

class RSSCommentsTest extends ShimmiePHPUnitTestCase
{
    public function testImageFeed(): void
    {
        global $user;
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        send_event(new CommentPostingEvent($image_id, $user, "ASDFASDF"));

        $this->get_page('rss/comments');
        //self::assert_mime(MimeType::RSS);
        self::assert_no_content("Exception");
        self::assert_content("ASDFASDF");
    }
}
