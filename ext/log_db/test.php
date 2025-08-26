<?php

declare(strict_types=1);

namespace Shimmie2;

final class LogDatabaseTest extends ShimmiePHPUnitTestCase
{
    public function testLog(): void
    {
        self::log_in_as_admin();
        self::get_page("log/view");
        self::get_page("log/view", ["r_module" => "core-image"]);
        self::get_page("log/view", ["r_time" => "2012-03-01"]);
        self::get_page("log/view", ["r_user" => "demo"]);

        $page = self::get_page("log/view", ["r_priority" => "10"]);
        self::assertEquals(200, $page->code);
    }

    public function testMessageRender(): void
    {
        $col = new MessageColumn("message", "Message");
        $html = $col->display(["priority" => 10, "message" => "Commented on Post #123 and then ate <script>cheese</script>"]);
        self::assertEquals(
            "<span class='level-debug'>Commented on <a href='/test/post/view/123'>&gt;&gt;123</a> and then ate &lt;script&gt;cheese&lt;/script&gt;</span>",
            (string)$html
        );
    }
}
