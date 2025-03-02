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
        $this->get_page("user/test");
        $this->assert_text("Posts uploaded</a>: 1");
    }

    public function testServeImage(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $page = $this->get_page("image/$image_id/moo.jpg");
        $this->assertEquals(200, $page->code);
    }

    public function testServeThumb(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $page = $this->get_page("thumb/$image_id/moo.jpg");
        $this->assertEquals(200, $page->code);
    }

    public function testDelete(): void
    {
        $this->log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        $this->log_in_as_user();
        $this->assertException(PermissionDenied::class, function () use ($image_id) {
            $this->post_page("image/delete", ['image_id' => "$image_id"]);
        });

        $this->log_in_as_admin();
        # delete twice because Trash extension
        $page = $this->post_page("image/delete", ['image_id' => "$image_id"]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
        $page = $this->post_page("image/delete", ['image_id' => "$image_id"]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        $this->assertException(PostNotFound::class, function () use ($image_id) {
            $this->get_page("image/$image_id/moo.jpg");
        });
    }

    public function testDeleteOwn(): void
    {
        UserClass::$known_classes["user"]->set_permission(ImagePermission::DELETE_OWN_IMAGE, true);
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        $this->log_in_as_user();
        # delete twice because Trash extension
        $page = $this->post_page("image/delete", ['image_id' => "$image_id"]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);
        $page = $this->post_page("image/delete", ['image_id' => "$image_id"]);
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        $this->assertException(PostNotFound::class, function () use ($image_id) {
            $this->get_page("image/$image_id/moo.jpg");
        });
    }
}
