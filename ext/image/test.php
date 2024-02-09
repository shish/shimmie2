<?php

declare(strict_types=1);

namespace Shimmie2;

class ImageIOTest extends ShimmiePHPUnitTestCase
{
    public function testUserStats(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        // broken with sqlite?
        //$this->get_page("user/test");
        //$this->assert_text("Images uploaded: 1");

        //$this->click("Images uploaded");
        //$this->assert_title("Image $image_id: test");

        # test that serving manually doesn't cause errors
        $page = $this->get_page("image/$image_id/moo.jpg");
        $this->assertEquals(200, $page->code);

        $page = $this->get_page("thumb/$image_id/moo.jpg");
        $this->assertEquals(200, $page->code);
    }

    public function testDeleteRequest(): void
    {
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        send_event(new PageRequestEvent("POST", "image/delete", [], ['image_id' => "$image_id"]));
        $this->assertTrue(true);  // FIXME: assert image was deleted?
    }
}
