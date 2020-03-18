<?php declare(strict_types=1);
class AdminPageTest extends ShimmiePHPUnitTestCase
{
    public function testAuth()
    {
        send_event(new UserLoginEvent(User::by_name(self::$anon_name)));
        $page = $this->get_page('admin');
        $this->assertEquals(403, $page->code);
        $this->assertEquals("Permission Denied", $page->title);

        send_event(new UserLoginEvent(User::by_name(self::$user_name)));
        $page = $this->get_page('admin');
        $this->assertEquals(403, $page->code);
        $this->assertEquals("Permission Denied", $page->title);

        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $page = $this->get_page('admin');
        $this->assertEquals(200, $page->code);
        $this->assertEquals("Admin Tools", $page->title);
    }

    public function testLowercaseAndSetCase()
    {
        // Create a problem
        $ts = time(); // we need a tag that hasn't been used before
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "TeStCase$ts");

        // Validate problem
        $page = $this->get_page("post/view/$image_id_1");
        $this->assertEquals("Image $image_id_1: TeStCase$ts", $page->title);

        // Fix
        send_event(new AdminActionEvent('lowercase_all_tags'));

        // Validate fix
        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Image $image_id_1: testcase$ts");

        // Change
        $_POST["tag"] = "TestCase$ts";
        send_event(new AdminActionEvent('set_tag_case'));

        // Validate change
        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Image $image_id_1: TestCase$ts");
    }

    # FIXME: make sure the admin tools actually work
    public function testRecount()
    {
        global $database;

        // Create a problem
        $ts = time(); // we need a tag that hasn't been used before
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $database->execute(
            "INSERT INTO tags(tag, count) VALUES(:tag, :count)",
            ["tag"=>"tes$ts", "count"=>42]
        );

        // Fix
        send_event(new AdminActionEvent('recount_tag_use'));

        // Validate fix
        $this->assertEquals(
            0,
            $database->get_one(
                "SELECT count FROM tags WHERE tag = :tag",
                ["tag"=>"tes$ts"]
            )
        );
    }

    public function testCommands()
    {
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        ob_start();
        send_event(new CommandEvent(["index.php", "help"]));
        send_event(new CommandEvent(["index.php", "get-page", "post/list"]));
        send_event(new CommandEvent(["index.php", "post-page", "post/list", "foo=bar"]));
        send_event(new CommandEvent(["index.php", "get-token"]));
        send_event(new CommandEvent(["index.php", "regen-thumb", "42"]));
        ob_end_clean();

        // don't crash
        $this->assertTrue(true);
    }
}
