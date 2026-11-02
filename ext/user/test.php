<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserPageTest extends ShimmiePHPUnitTestCase
{
    public function testUserPage(): void
    {
        $page = self::get_page('user');
        self::assertSame(PageMode::REDIRECT, $page->mode);

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
        self::log_in_as_admin();
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
        self::assertException(UserNotFound::class, function () {
            User::by_name('testnew');
        });

        self::log_in_as_admin();
        $page = self::post_page('user_admin/create_other', [
            'name' => 'testnew',
            'pass1' => 'testnew',
            'pass2' => 'testnew',
            'email' => '',
        ]);
        self::assertSame(302, $page->code);
        User::by_name('testnew');
    }

    public function testChangeOwnPasswordWithoutCurrent(): void
    {
        // User tries to change their own password without providing current password
        self::log_in_as_user();
        self::assertException(InvalidInput::class, function () {
            self::post_page('user_admin/change_pass', [
                'id' => (string)Ctx::$user->id,
                'pass1' => 'newpass',
                'pass2' => 'newpass',
                'current_password' => '',
            ]);
        });
    }

    public function testChangeOwnPasswordWithWrongCurrent(): void
    {
        // User tries to change their own password with wrong current password
        self::log_in_as_user();
        self::assertException(InvalidInput::class, function () {
            self::post_page('user_admin/change_pass', [
                'id' => (string)Ctx::$user->id,
                'pass1' => 'newpass',
                'pass2' => 'newpass',
                'current_password' => 'wrongpass',
            ]);
        });
    }

    public function testChangeOwnPasswordWithCorrectCurrent(): void
    {
        // User changes their own password with correct current password
        self::log_in_as_user();
        $page = self::post_page('user_admin/change_pass', [
            'id' => (string)Ctx::$user->id,
            'pass1' => 'newpass',
            'pass2' => 'newpass',
            'current_password' => 'test',  // original password
        ]);
        self::assertSame(302, $page->code);

        // Verify the password was changed by trying to log in with new password
        self::log_out();
        // Can't directly test login without more setup, but at least verify no exception
    }

    public function testAdminCanChangeOtherPasswordWithoutCurrent(): void
    {
        // Admin (with EDIT_USER_PASSWORD permission) can change another user's password
        // without providing current password
        self::log_in_as_admin();
        $test_user_id = (string)Ctx::$database->get_one("SELECT id FROM users WHERE name = 'test'");
        $page = self::post_page('user_admin/change_pass', [
            'id' => $test_user_id,
            'pass1' => 'newpass',
            'pass2' => 'newpass',
            // Note: no current_password provided
        ]);
        self::assertSame(302, $page->code);
    }
}
