<?php

declare(strict_types=1);

namespace Shimmie2;

class ViewImageTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // FIXME: upload images
    }

    public function testViewPage()
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "test");

        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Post $image_id_1: test");
    }

    public function testViewInfo()
    {
        global $config;

        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "test");

        $config->set_string(ImageConfig::INFO, '$size // $filesize // $ext');
        $this->get_page("post/view/$image_id_1");
        $this->assert_text("640x480 // 19.3KB // jpg");
    }

    public function testPrevNext()
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "test2");
        $image_id_3 = $this->post_image("tests/favicon.png", "test");

        // Front image: no next, has prev
        $page = $this->get_page("post/next/$image_id_1");
        $this->assertEquals(404, $page->code);
        $page = $this->get_page("post/prev/$image_id_1");
        $this->assertEquals("/test/post/view/$image_id_2", $page->redirect);

        // When searching, we skip the middle
        $page = $this->get_page("post/prev/$image_id_1", ["search" => "test"]);
        $this->assertEquals("/test/post/view/$image_id_3?#search=test", $page->redirect);

        $page = $this->get_page("post/next/$image_id_3", ["search" => "test"]);
        $this->assertEquals("/test/post/view/$image_id_1?#search=test", $page->redirect);

        // Middle image: has next and prev
        $page = $this->get_page("post/next/$image_id_2");
        $this->assertEquals("/test/post/view/$image_id_1", $page->redirect);
        $page = $this->get_page("post/prev/$image_id_2");
        $this->assertEquals("/test/post/view/$image_id_3", $page->redirect);

        // Last image has next, no prev
        $page = $this->get_page("post/next/$image_id_3");
        $this->assertEquals("/test/post/view/$image_id_2", $page->redirect);
        $page = $this->get_page("post/prev/$image_id_3");
        $this->assertEquals(404, $page->code);
    }

    public function testView404()
    {
        $this->log_in_as_user();
        $image_id_1 = $this->post_image("tests/favicon.png", "test");
        $idp1 = $image_id_1 + 1;

        $this->get_page("post/view/$idp1");
        $this->assert_title('Post not found');

        $this->get_page('post/view/-1');
        $this->assert_title('Post not found');
    }
}
