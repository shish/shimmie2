<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostLockTest extends ShimmiePHPUnitTestCase
{
    public function testLockEdit(): void
    {
        // user can post
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        // admin can lock
        self::log_in_as_admin();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, new QueryArray(["locked" => "on"])));

        // user can't edit locked post
        self::log_in_as_user();
        self::assertException(PermissionDenied::class, function () use ($image_id) {
            send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, new QueryArray(["source" => "http://example.com"])));
        });

        // admin can edit locked post
        self::log_in_as_admin();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, new QueryArray(["source" => "http://example.com"])));

        // admin can unlock
        self::log_in_as_admin();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, new QueryArray([]))); // "locked" is not set

        // user can edit un-locked post
        self::log_in_as_user();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, new QueryArray(["source" => "http://example.com"])));
    }
}
