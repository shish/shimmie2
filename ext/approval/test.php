<?php

declare(strict_types=1);

namespace Shimmie2;

final class ApprovalTest extends ShimmiePHPUnitTestCase
{
    public function testNoApprovalNeeded(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "some_tag");
        self::assert_search_results(["some_tag"], [$image_id]);
    }

    /*
    // Approvals are always automatic in unit-test mode,
    // so we can't test this
    public function testApprovalNeeded(): void
    {
        // use can post but not see what they posted
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "some_tag");
        self::assert_search_results(["some_tag"], []);

        // admin can approve
        self::log_in_as_admin();
        self::assert_search_results(["some_tag"], []);
        self::post_page("approve_image/$image_id");
        self::assert_search_results(["some_tag"], [$image_id]);

        // use then sees the image
        self::log_in_as_user();
        self::assert_search_results(["some_tag"], [$image_id]);
    }
    */
}
