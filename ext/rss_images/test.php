<?php

declare(strict_types=1);

namespace Shimmie2;

final class RSSImagesTest extends ShimmiePHPUnitTestCase
{
    public function testImageFeed(): void
    {
        self::log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        self::log_out();

        self::get_page('rss/images');
        //self::assert_mime(MimeType::RSS);
        self::assert_no_content("Exception");

        self::get_page('rss/images/1');
        //self::assert_mime(MimeType::RSS);
        self::assert_no_content("Exception");

        # FIXME: test that the image is actually found
        self::get_page('rss/images/computer/1');
        //self::assert_mime(MimeType::RSS);
        self::assert_no_content("Exception");

        # valid tag, invalid page
        self::get_page('rss/images/computer/2');
        //self::assert_mime(MimeType::RSS);
        self::assert_no_content("Exception");

        # not found
        self::get_page('rss/images/waffle/2');
        //self::assert_mime(MimeType::RSS);
        self::assert_no_content("Exception");
    }
}
