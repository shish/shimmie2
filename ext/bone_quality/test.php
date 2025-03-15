<?php

declare(strict_types=1);

namespace Shimmie2;

final class BoneQualityTest extends ShimmiePHPUnitTestCase
{
    public function testBoneQualityPage(): void
    {
        $page = self::get_page('bone_quality');
        self::assert_title("review your fate");
        self::assert_text("Congratulations");
        self::assert_text("tagme</a> remaining: <span>0");
    }

    public function testChoreSearch(): void
    {
        self::log_in_as_user();

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "tagme");
        self::get_page("bone_quality");
        self::assert_text("Congratulations");
        self::assert_text("tagme</a> remaining: <span>1");

        $image = Image::by_id_ex($image_id);
        send_event(new TagSetEvent($image, ["new_tag"]));

        self::get_page("bone_quality");
        self::assert_text("Congratulations");
        self::assert_text("tagme</a> remaining: <span>0");
    }

    public function testChoreThreshold(): void
    {
        global $config;
        $config->set_int(BoneQualityConfig::CHORE_THRESHOLD, 0);

        self::log_in_as_user();

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "tagme");
        self::get_page("bone_quality");
        self::assert_no_text("Congratulations");
    }
}
