<?php

declare(strict_types=1);

namespace Shimmie2;

class ApprovalTest extends ShimmiePHPUnitTestCase
{
    public function testNoApprovalNeeded(): void
    {
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "some_tag");
        $this->assert_search_results(["some_tag"], [$image_id]);
    }

    public function testApprovalNeeded(): void
    {
        global $config;
        $config->set_bool(ApprovalConfig::IMAGES, true);

        // use can post but not see what they posted
        $this->log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "some_tag");
        $this->assert_search_results(["some_tag"], []);

        // admin can approve
        $this->log_in_as_admin();
        $this->assert_search_results(["some_tag"], []);
        $this->post_page("approve_image/$image_id");
        $this->assert_search_results(["some_tag"], [$image_id]);

        // use then sees the image
        $this->log_in_as_user();
        $this->assert_search_results(["some_tag"], [$image_id]);
    }

    public function tearDown(): void
    {
        global $config;
        $config->set_bool(ApprovalConfig::IMAGES, false);
        parent::tearDown();
    }
}
