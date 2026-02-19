<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostOwnerTest extends ShimmiePHPUnitTestCase
{
    public function testOwnerEdit(): void
    {
        self::log_in_as_user();
        $image_id = $this->create_post("tests/pbx_screenshot.jpg", "pbx");
        $image = Post::by_id_ex($image_id);

        self::log_in_as_admin();
        send_event(new PostInfoSetEvent($image, 0, new QueryArray(["owner" => self::ADMIN_NAME])));

        self::log_in_as_user();
        self::get_page("post/view/$image_id");
        self::assert_text(self::ADMIN_NAME);
    }
}
