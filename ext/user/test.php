<?php

declare(strict_types=1);

namespace Shimmie2;

class UserPageTest extends ShimmiePHPUnitTestCase
{
    public function testUserPage(): void
    {
        $page = $this->get_page('user');
        self::assertEquals(PageMode::REDIRECT, $page->mode);

        $this->get_page('user/demo');
        self::assert_title("demo's Page");
        self::assert_text("Joined:");
        self::assert_no_text("Operations");

        self::assertException(UserNotFound::class, function () {
            $this->get_page('user/MauMau');
        });

        $this->log_in_as_user();
        // should be on the user page
        $this->get_page('user/test');
        self::assert_title("test's Page");
        self::assert_text("Operations");
        // FIXME: check class
        //self::assert_no_text("Admin:");
        $this->log_out();

        $this->log_in_as_admin();
        // should be on the user page
        $this->get_page('user/demo');
        self::assert_title("demo's Page");
        self::assert_text("Operations");
        // FIXME: check class
        //self::assert_text("Admin:");
        $this->log_out();
    }

    # FIXME: test user creation
    # FIXME: test adminifying
    # FIXME: test password reset
    public function testUserList(): void
    {
        $this->get_page('user_admin/list');
        self::assert_text("demo");
    }

    public function testCreateOther(): void
    {
        global $page;

        self::assertException(PermissionDenied::class, function () {
            $this->log_out();
            $this->post_page('user_admin/create_other', [
                'name' => 'testnew',
                'pass1' => 'testnew',
                'pass2' => 'testnew',
                'email' => '',
            ]);
        });
        self::assertException(UserNotFound::class, function () {User::by_name('testnew');});

        $this->log_in_as_admin();
        $this->post_page('user_admin/create_other', [
            'name' => 'testnew',
            'pass1' => 'testnew',
            'pass2' => 'testnew',
            'email' => '',
        ]);
        self::assertEquals(302, $page->code);
        User::by_name('testnew');
    }
}
