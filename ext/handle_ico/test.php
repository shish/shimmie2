<?php declare(strict_types=1);
class IcoFileHandlerTest extends ShimmiePHPUnitTestCase
{
    public function testIcoHander()
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("ext/handle_static/static/favicon.ico", "shimmie favicon");
        $this->get_page("post/view/$image_id"); // test for no crash

        # FIXME: test that the thumb works
        # FIXME: test that it gets displayed properly
    }
}
