<?php

declare(strict_types=1);

namespace Shimmie2;

final class ImageIOTest extends ShimmiePHPUnitTestCase
{
    public function testUserStats(): void
    {
        self::log_in_as_user();
        $this->post_image("tests/pbx_screenshot.jpg", "test");

        // broken with sqlite?
        self::get_page("user/test");
        self::assert_text("Posts uploaded</a>: 1");
    }

    public function testServeImage(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $page = self::get_page("image/$image_id/moo.jpg");
        self::assertEquals(200, $page->code);
    }

    public function testServeThumb(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
        $page = self::get_page("thumb/$image_id/moo.jpg");
        self::assertEquals(200, $page->code);
    }

    public function testDelete(): void
    {
        self::log_in_as_admin();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        self::log_in_as_user();
        self::assertException(PermissionDenied::class, function () use ($image_id) {
            self::post_page("image/delete", ['image_id' => "$image_id"]);
        });

        self::log_in_as_admin();
        # delete twice because Trash extension
        $page = self::post_page("image/delete", ['image_id' => "$image_id"]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);
        $page = self::post_page("image/delete", ['image_id' => "$image_id"]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        self::assertException(PostNotFound::class, function () use ($image_id) {
            self::get_page("image/$image_id/moo.jpg");
        });
    }

    public function testDeleteOwn(): void
    {
        UserClass::$known_classes["user"]->set_permission(ImagePermission::DELETE_OWN_IMAGE, true);
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

        self::log_in_as_user();
        # delete twice because Trash extension
        $page = self::post_page("image/delete", ['image_id' => "$image_id"]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);
        $page = self::post_page("image/delete", ['image_id' => "$image_id"]);
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        self::assertException(PostNotFound::class, function () use ($image_id) {
            self::get_page("image/$image_id/moo.jpg");
        });
    }
}
