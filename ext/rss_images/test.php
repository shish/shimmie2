<?php

declare(strict_types=1);

namespace Shimmie2;

class RSSImagesTest extends ShimmiePHPUnitTestCase
{
    public function testImageFeed(): void
    {
        $this->log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
        $this->log_out();

        $this->get_page('rss/images');
        //$this->assert_mime(MimeType::RSS);
        $this->assert_no_content("Exception");

        $this->get_page('rss/images/1');
        //$this->assert_mime(MimeType::RSS);
        $this->assert_no_content("Exception");

        # FIXME: test that the image is actually found
        $this->get_page('rss/images/computer/1');
        //$this->assert_mime(MimeType::RSS);
        $this->assert_no_content("Exception");

        # valid tag, invalid page
        $this->get_page('rss/images/computer/2');
        //$this->assert_mime(MimeType::RSS);
        $this->assert_no_content("Exception");

        # not found
        $this->get_page('rss/images/waffle/2');
        //$this->assert_mime(MimeType::RSS);
        $this->assert_no_content("Exception");
    }
}
