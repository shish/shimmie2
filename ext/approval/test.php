<?php

declare(strict_types=1);

namespace Shimmie2;

final class ApprovalTest extends ShimmiePHPUnitTestCase
{
    // Everything is auto-approved in unit-test mode,
    public function testNoApprovalNeeded(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "some_tag");
        self::assert_search_results(["some_tag"], [$image_id]);
    }

    /**
     * Test that users can search for their own unapproved posts with approved=no
     */
    public function testUserCanSeeOwnUnapprovedPosts(): void
    {
        self::log_in_as_user();

        // Post an image
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "user_test");

        // In unit test mode, images are auto-approved, so manually disapprove it
        self::log_in_as_admin();
        Approval::disapprove_image($image_id);

        // User should be able to see their own unapproved posts with approved=no
        self::log_in_as_user();
        self::assert_search_results(["approved=no"], [$image_id], "User can find their own unapproved post");
    }

    /**
     * Test that users cannot see other users' unapproved posts
     */
    public function testUserCannotSeeOthersUnapprovedPosts(): void
    {
        // We'll just test that when an admin posts an unapproved image,
        // a regular user can't search for it with approved=no
        self::log_in_as_admin();
        $admin_image_id = $this->post_image("tests/favicon.png", "admin_test");
        Approval::disapprove_image($admin_image_id);

        // User should not find admin's unapproved posts
        self::log_in_as_user();
        self::assert_search_results(["approved=no"], [], "User cannot see other users' unapproved posts");
    }

    /**
     * Test that approved posts are always visible in approved=no search for admins only
     */
    public function testAdminCanSeeAllUnapprovedPosts(): void
    {
        self::log_in_as_user();
        $user_image_id = $this->post_image("tests/pbx_screenshot.jpg", "user_img");

        self::log_in_as_admin();
        Approval::disapprove_image($user_image_id);

        // Admin can see unapproved posts
        self::assert_search_results(["approved=no"], [$user_image_id], "Admin can see unapproved posts");
    }

    /**
     * Test that anonymous users cannot search for unapproved posts
     */
    public function testAnonCannotSeeUnapprovedPosts(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "anon_test");

        self::log_in_as_admin();
        Approval::disapprove_image($image_id);

        // Anonymous users should not find unapproved posts
        self::log_out();
        self::assert_search_results(["approved=no"], [], "Anonymous users cannot see unapproved posts");
    }

    /**
     * Test that approved=yes returns only approved posts
     */
    public function testApprovedYesFilter(): void
    {
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "approved_test");

        // In unit test mode, images are auto-approved
        self::assert_search_results(["approved=yes"], [$image_id], "approved=yes finds approved posts");
    }

    /**
     * Test that default search (no approved filter) shows only approved posts for users
     */
    public function testDefaultSearchShowsApprovedOnly(): void
    {
        self::log_in_as_user();
        $approved_image_id = $this->post_image("tests/pbx_screenshot.jpg", "shared_tag");

        self::log_in_as_admin();
        $unapproved_image_id = $this->post_image("tests/favicon.png", "shared_tag");
        Approval::disapprove_image($unapproved_image_id);

        // Default search should only show approved posts for regular users
        self::log_in_as_user();
        // When searching without approved filter, only approved posts should be found
        #$img1 = Image::by_id_ex($approved_image_id);
        #$img2 = Image::by_id_ex($unapproved_image_id);
        #throw new \Exception("img1 approved: " . ($img1['approved'] ? "yes" : "no") . ", img2 approved: " . ($img2['approved'] ? "yes" : "no"));
        self::assert_search_results(["shared_tag"], [$approved_image_id], "Only approved posts found in regular search");

        // Even when explicitly saying approved=yes, should only find approved posts
        self::assert_search_results(["shared_tag", "approved=yes"], [$approved_image_id], "Only approved posts found when explicitly searching approved=yes");
    }

    // Test "not approved yet" case by posting and then immediately disapproving
    public function testApprovalNeeded(): void
    {
        // use can post but not see what they posted
        self::log_in_as_user();
        $image_id = $this->post_image("tests/pbx_screenshot.jpg", "some_tag");
        Approval::disapprove_image($image_id);
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
}
