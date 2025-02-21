<?php

declare(strict_types=1);

namespace Shimmie2;

class BoneQualityTest extends ShimmiePHPUnitTestCase
{
    public function testBoneQualityPage(): void
    {
        $page = $this->get_page('bone_quality');
        $this->assert_title("review your fate");
        $this->assert_text("Congratulations");
        $this->assert_text("tagme</a> posts remaining: <span>0");
    }

    public function testChoreSearch(): void
    {
        $this->log_in_as_user();

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "tagme");
        $this->get_page("bone_quality");
        $this->assert_text("Congratulations");
        $this->assert_text("tagme</a> posts remaining: <span>1");

        $image = Image::by_id_ex($image_id);
        send_event(new TagSetEvent($image, ["new_tag"]));

        $this->get_page("bone_quality");
        $this->assert_text("Congratulations");
        $this->assert_text("tagme</a> posts remaining: <span>0");
    }

    public function testChoreThreshold(): void
    {
        global $config;
        $config->set_int(BoneQualityConfig::CHORE_THRESHOLD, 0);

        $this->log_in_as_user();

        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "tagme");
        $this->get_page("bone_quality");
        $this->assert_no_text("Congratulations");
    }

    // reset the config to defaults at the end of every test so
    // that it doesn't mess with other unrelated tests
    public function tearDown(): void
    {
        global $config;
        $config->set_int(BoneQualityConfig::CHORE_THRESHOLD, 20);
        parent::tearDown();
    }
}
