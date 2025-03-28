<?php

declare(strict_types=1);

namespace Shimmie2;

final class ViewPostTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // FIXME: upload images
    }

    public function testViewPage(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "test");

        self::get_page("post/view/$image_id_1");
        self::assert_title("Post $image_id_1: test");
    }

    public function testViewInfo(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "test");

        Ctx::$config->set(ImageConfig::INFO, '$size // $filesize // $ext');
        self::get_page("post/view/$image_id_1");
        self::assert_text("640x480 // 19KB // jpg");
    }

    public function testPrevNext(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $image_id_2 = $this->post_image("tests/bedroom_workshop.jpg", "test2");
        $image_id_3 = $this->post_image("tests/favicon.png", "test");

        // Front image: no next, has prev
        self::assertException(PostNotFound::class, function () use ($image_id_1) {
            self::get_page("post/next/$image_id_1");
        });
        $page = self::get_page("post/prev/$image_id_1");
        self::assertEquals("/test/post/view/$image_id_2", $page->redirect);

        // When searching, we skip the middle
        $page = self::get_page("post/prev/$image_id_1", ["search" => "test"]);
        self::assertEquals("/test/post/view/$image_id_3#search=test", $page->redirect);

        $page = self::get_page("post/next/$image_id_3", ["search" => "test"]);
        self::assertEquals("/test/post/view/$image_id_1#search=test", $page->redirect);

        // Middle image: has next and prev
        $page = self::get_page("post/next/$image_id_2");
        self::assertEquals("/test/post/view/$image_id_1", $page->redirect);
        $page = self::get_page("post/prev/$image_id_2");
        self::assertEquals("/test/post/view/$image_id_3", $page->redirect);

        // Last image has next, no prev
        $page = self::get_page("post/next/$image_id_3");
        self::assertEquals("/test/post/view/$image_id_2", $page->redirect);
        self::assertException(PostNotFound::class, function () use ($image_id_3) {
            self::get_page("post/prev/$image_id_3");
        });
    }

    public function testPrevNextDisabledWhenOrdered(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        self::get_page("post/view/$image_id");
        self::assert_text("Prev");

        self::get_page("post/view/$image_id", ["search" => "test"]);
        self::assert_text("Prev");

        self::get_page("post/view/$image_id", ["search" => "cake_order:_the_cakening"]);
        self::assert_text("Prev");

        self::get_page("post/view/$image_id", ["search" => "order:score"]);
        self::assert_no_text("Prev");
    }

    public function testView404(): void
    {
        self::log_in_as_user();
        $image_id_1 = $this->post_image("tests/favicon.png", "test");
        $idp1 = $image_id_1 + 1;

        self::assertException(PostNotFound::class, function () use ($idp1) {
            self::get_page("post/view/$idp1");
        });
        self::assertException(PostNotFound::class, function () {
            self::get_page('post/view/-1');
        });
    }
}
