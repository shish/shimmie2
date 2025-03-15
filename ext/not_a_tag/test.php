<?php

declare(strict_types=1);

namespace Shimmie2;

final class NotATagTest extends ShimmiePHPUnitTestCase
{
    public function testUntags(): void
    {
        global $database;
        $database->execute("DELETE FROM untags");
        $database->execute("INSERT INTO untags(tag, redirect) VALUES (:tag, :redirect)", ["tag" => "face", "redirect" => "no-body-parts.html"]);

        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        // Original
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: pbx");

        // Modified OK
        send_event(new TagSetEvent($image, ["two"]));
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: two");

        // Modified Bad as user - redirect
        self::assertException(TagSetException::class, function () use ($image) {
            send_event(new TagSetEvent($image, ["three", "face"]));
        });
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: two");

        // Modified Bad as admin - ignore (should warn?)
        self::log_in_as_admin();
        send_event(new TagSetEvent($image, ["four", "face"]));
        self::get_page("post/view/$image_id");
        self::assert_title("Post $image_id: four");
    }
}
