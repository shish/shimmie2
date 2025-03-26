<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserPageTest extends ShimmiePHPUnitTestCase
{
    public function testUserPage(): void
    {
        $page = self::get_page('user');
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        self::get_page('user/demo');
        self::assert_title("demo's Page");
        self::assert_text("Joined:");
        self::assert_no_text("Operations");

        self::assertException(UserNotFound::class, function () {
            self::get_page('user/MauMau');
        });

        self::log_in_as_user();
        // should be on the user page
        self::get_page('user/test');
        self::assert_title("test's Page");
        self::assert_text("Operations");
        // FIXME: check class
        //self::assert_no_text("Admin:");
        self::log_out();

        self::log_in_as_admin();
        // should be on the user page
        self::get_page('user/demo');
        self::assert_title("demo's Page");
        self::assert_text("Operations");
        // FIXME: check class
        //self::assert_text("Admin:");
        self::log_out();
    }

    # FIXME: test user creation
    # FIXME: test adminifying
    # FIXME: test password reset
    public function testUserList(): void
    {
        self::get_page('user_admin/list');
        self::assert_text("demo");
    }

    public function testCreateOther(): void
    {
        self::assertException(PermissionDenied::class, function () {
            self::log_out();
            self::post_page('user_admin/create_other', [
                'name' => 'testnew',
                'pass1' => 'testnew',
                'pass2' => 'testnew',
                'email' => '',
            ]);
        });
        self::assertException(UserNotFound::class, function () {User::by_name('testnew');});

        self::log_in_as_admin();
        $page = self::post_page('user_admin/create_other', [
            'name' => 'testnew',
            'pass1' => 'testnew',
            'pass2' => 'testnew',
            'email' => '',
        ]);
        self::assertEquals(302, $page->code);
        User::by_name('testnew');
    }
}
