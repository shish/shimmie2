<?php

declare(strict_types=1);

namespace Shimmie2;

class PermManagerTest extends ShimmiePHPUnitTestCase
{
    public function testAuth(): void
    {
        $this->log_in_as_admin();
        $this->get_page('perm_manager');
        $this->assert_title("User Classes");
        //$this->assert_text("SimpleTest integration"); // FIXME: something which still exists
    }

    public function test_new_class(): void
    {
        global $database;

        $this->log_in_as_admin();
        $this->assertFalse(in_array("test1", UserClass::$known_classes));
        $page = $this->post_page("perm_manager/new", ["new_name" => "test1", "new_parent" => "base"]);
        $this->assert_response(302);
        $this->assertEquals(1, $database->get_one("SELECT COUNT(*) FROM permissions WHERE class = :class AND parent = :parent", ["class" => "test1", "parent" => "base"]));
        $this->assertTrue(in_array("test1", array_keys(UserClass::$known_classes)));
    }

    public function test_duplicate_class(): void
    {
        global $database;

        $this->log_in_as_admin();
        $page = $this->post_page("perm_manager/new", ["new_name" => "user", "new_parent" => "base"]);
        $this->assert_response(422);
        $this->assertEquals(1, $database->get_one("SELECT COUNT(*) FROM permissions WHERE class = :class", ["class" => "user"]));
    }

    public function test_modify_class(): void
    {
        $this->log_in_as_admin();
        $page = $this->post_page("perm_manager/new", ["new_name" => "test1", "new_parent" => "base"]);

        // add comment permissions, add bypass permission
        $page = $this->post_page("perm_manager/test1/set_perms", ["perm_bypass_image_approval" => "on", "perm_create_comment" => "on"]);
        $cls = UserClass::$known_classes["test1"];
        $this->assertTrue($cls->can(Permissions::BYPASS_IMAGE_APPROVAL));
        $this->assertTrue($cls->can(Permissions::CREATE_COMMENT));
        $this->assertFalse($cls->can(Permissions::BIG_SEARCH));

        // remove comment permission
        $page = $this->post_page("perm_manager/test1/set_perms", ["perm_bypass_image_approval" => "on"]);
        $cls = UserClass::$known_classes["test1"];
        $this->assertTrue($cls->can(Permissions::BYPASS_IMAGE_APPROVAL));
        $this->assertFalse($cls->can(Permissions::CREATE_COMMENT));
        $this->assertFalse($cls->can(Permissions::BIG_SEARCH));

        // grant all user permissions, retain bypass permission
        $page = $this->post_page("perm_manager/test1/set_parent", ["parent" => "user"]);
        $cls = UserClass::$known_classes["test1"];
        $this->assertTrue($cls->can(Permissions::BYPASS_IMAGE_APPROVAL));
        $this->assertTrue($cls->can(Permissions::CREATE_COMMENT));
        $this->assertTrue($cls->can(Permissions::BIG_SEARCH));
    }

    public function test_delete_class(): void
    {
        global $database;

        $this->log_in_as_admin();
        $page = $this->post_page("perm_manager/new", ["new_name" => "test1", "new_parent" => "base"]);

        // incorrect verification name
        $page = $this->post_page("perm_manager/test1/delete", ["name" => "testone"]);
        $this->assert_response(400);
        $this->assertEquals(1, $database->get_one("SELECT COUNT(*) FROM permissions WHERE class = :class", ["class" => "test1"]));

        // is a core class
        $page = $this->post_page("perm_manager/ghost/delete", ["name" => "ghost"]);
        $this->assert_response(422);
        $this->assertEquals(1, $database->get_one("SELECT COUNT(*) FROM permissions WHERE class = :class", ["class" => "ghost"]));

        // has a child class
        $page = $this->post_page("perm_manager/new", ["new_name" => "test2", "new_parent" => "test1"]);
        $page = $this->post_page("perm_manager/test1/delete", ["name" => "test1"]);
        $this->assert_response(422);
        $this->assertEquals(1, $database->get_one("SELECT COUNT(*) FROM permissions WHERE class = :class", ["class" => "test1"]));

        $page = $this->post_page("perm_manager/test2/delete", ["name" => "test2"]);
        $this->assert_response(302);
        $this->assertEquals(0, $database->get_one("SELECT COUNT(*) FROM permissions WHERE class = :class", ["class" => "test2"]));
        $page = $this->post_page("perm_manager/test1/delete", ["name" => "test1"]);
        $this->assert_response(302);
        $this->assertEquals(0, $database->get_one("SELECT COUNT(*) FROM permissions WHERE class = :class", ["class" => "test1"]));
        $this->assertFalse(in_array("test1", UserClass::$known_classes));
    }
}
