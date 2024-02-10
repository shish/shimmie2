<?php

declare(strict_types=1);

namespace Shimmie2;

class TagToolsTest extends ShimmiePHPUnitTestCase
{
    public function testLowercaseAndSetCase(): void
    {
        // Create a problem
        $ts = time(); // we need a tag that hasn't been used before
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $image_id_1 = $this->post_image("tests/pbx_screenshot.jpg", "TeStCase$ts");

        // Validate problem
        $page = $this->get_page("post/view/$image_id_1");
        $this->assertEquals("Post $image_id_1: TeStCase$ts", $page->title);

        // Fix
        send_event(new AdminActionEvent('lowercase_all_tags', []));

        // Validate fix
        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Post $image_id_1: testcase$ts");

        // Change
        send_event(new AdminActionEvent('set_tag_case', ["tag" => "TestCase$ts"]));

        // Validate change
        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Post $image_id_1: TestCase$ts");
    }

    # FIXME: make sure the admin tools actually work
    public function testRecount(): void
    {
        global $database;

        // Create a problem
        $ts = time(); // we need a tag that hasn't been used before
        send_event(new UserLoginEvent(User::by_name(self::$admin_name)));
        $database->execute(
            "INSERT INTO tags(tag, count) VALUES(:tag, :count)",
            ["tag" => "tes$ts", "count" => 42]
        );

        // Fix
        send_event(new AdminActionEvent('recount_tag_use', []));

        // Validate fix
        $this->assertEquals(
            0,
            $database->get_one(
                "SELECT count FROM tags WHERE tag = :tag",
                ["tag" => "tes$ts"]
            )
        );
    }
}
