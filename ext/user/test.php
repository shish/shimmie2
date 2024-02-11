<?php

declare(strict_types=1);

namespace Shimmie2;

class UserPageTest extends ShimmiePHPUnitTestCase
{
    public function testUserPage(): void
    {
        $page = $this->get_page('user');
        $this->assertEquals(PageMode::REDIRECT, $page->mode);

        $this->get_page('user/demo');
        $this->assert_title("demo's Page");
        $this->assert_text("Joined:");
        $this->assert_no_text("Operations");

        $this->get_page('user/MauMau');
        $this->assert_title("No Such User");

        $this->log_in_as_user();
        // should be on the user page
        $this->get_page('user/test');
        $this->assert_title("test's Page");
        $this->assert_text("Operations");
        // FIXME: check class
        //$this->assert_no_text("Admin:");
        $this->log_out();

        $this->log_in_as_admin();
        // should be on the user page
        $this->get_page('user/demo');
        $this->assert_title("demo's Page");
        $this->assert_text("Operations");
        // FIXME: check class
        //$this->assert_text("Admin:");
        $this->log_out();
    }

    # FIXME: test user creation
    # FIXME: test adminifying
    # FIXME: test password reset
    public function testUserList(): void
    {
        $this->get_page('user_admin/list');
        $this->assert_text("demo");
    }

    public function testUserClasses(): void
    {
        $this->get_page('user_admin/classes');
        $this->assert_text("admin");
    }

    public function testCreateOther(): void
    {
        global $page;

        $this->assertException(PermissionDenied::class, function () {
            $this->log_out();
            $this->post_page('user_admin/create_other', [
                'name' => 'testnew',
                'pass1' => 'testnew',
                'email' => '',
            ]);
        });
        $this->assertNull(User::by_name('testnew'), "Anon can't create others");

        $this->log_in_as_admin();
        $this->post_page('user_admin/create_other', [
            'name' => 'testnew',
            'pass1' => 'testnew',
            'email' => '',
        ]);
        $this->assertEquals(302, $page->code);
        $this->assertNotNull(User::by_name('testnew'), "Admin can create others");
    }
}
