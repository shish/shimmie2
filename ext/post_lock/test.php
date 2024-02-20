<?php

declare(strict_types=1);

namespace Shimmie2;

class PostLockTest extends ShimmiePHPUnitTestCase
{
    public function testLockEdit(): void
    {
        // user can post
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

        // admin can lock
        $this->log_in_as_admin();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, ["locked" => "on"]));

        // user can't edit locked post
        $this->log_in_as_user();
        $this->assertException(PermissionDenied::class, function () use ($image_id) {
            send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, ["source" => "http://example.com"]));
        });

        // admin can edit locked post
        $this->log_in_as_admin();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, ["source" => "http://example.com"]));

        // admin can unlock
        $this->log_in_as_admin();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, [])); // "locked" is not set

        // user can edit un-locked post
        $this->log_in_as_user();
        send_event(new ImageInfoSetEvent(Image::by_id_ex($image_id), 0, ["source" => "http://example.com"]));
    }
}
