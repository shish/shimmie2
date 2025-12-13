<?php

declare(strict_types=1);

namespace Shimmie2;

final class ExtManagerTest extends ShimmiePHPUnitTestCase
{
    public function testDoc(): void
    {
        self::get_page('ext_doc/ext_manager');
        self::assert_title("Documentation for Extension Manager");
        self::assert_text("(This extension has no documentation)");

        # test author without email
        self::get_page('ext_doc/user');
    }

    public function testManage(): void
    {
        self::log_in_as_admin();
        self::get_page('ext_manager');
        self::assert_title("Extensions");
        self::assert_text("Image Files");
        self::log_out();

        # FIXME: test that some extensions can be added and removed? :S
    }

    public function testApiExtensions(): void
    {
        // Test as anonymous user - should see DEFAULT visibility extensions only
        $page = self::get_page('api/internal/extensions');
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        $data = json_decode($page->data, true);
        self::assertIsArray($data);
        // Should contain extensions with DEFAULT visibility (autocomplete doesn't set visibility, so defaults to DEFAULT)
        self::assertContains('autocomplete', $data);

        // Test as admin - should see both DEFAULT and ADMIN visibility extensions
        self::log_in_as_admin();
        $page = self::get_page('api/internal/extensions');
        self::assertEquals(200, $page->code);
        self::assertEquals(PageMode::DATA, $page->mode);
        $admin_data = json_decode($page->data, true);
        self::assertIsArray($admin_data);
        // Admin should see at least as many extensions as anonymous users
        self::assertGreaterThanOrEqual(count($data), count($admin_data));
        self::log_out();
    }
}
