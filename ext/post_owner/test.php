<?php

declare(strict_types=1);

namespace Shimmie2;

class PostOwnerTest extends ShimmiePHPUnitTestCase
{
    public function testOwnerEdit(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
        $image = Image::by_id_ex($image_id);

        $this->log_in_as_admin();
        send_event(new ImageInfoSetEvent($image, 0, ["owner" => self::$admin_name]));

        $this->log_in_as_user();
        $this->get_page("post/view/$image_id");
        $this->assert_text(self::$admin_name);
    }
}
