<?php

declare(strict_types=1);

namespace Shimmie2;

final class DanbooruApiTest extends ShimmiePHPUnitTestCase
{
    public function testSearch(): void
    {
        self::log_in_as_admin();

        $image_id = $this->post_image("tests/bedroom_workshop.jpg", "data");

        self::get_page("api/danbooru/find_posts");
        self::get_page("api/danbooru/find_posts", ["id" => (string)$image_id]);
        self::get_page("api/danbooru/find_posts", ["md5" => "17fc89f372ed3636e28bd25cc7f3bac1"]);
        self::get_page("api/danbooru/find_posts", ["tags" => "*"]);

        self::get_page("api/danbooru/find_tags");
        self::get_page("api/danbooru/find_tags", ["id" => "1"]);
        self::get_page("api/danbooru/find_tags", ["name" => "data"]);

        $page = self::get_page("api/danbooru/post/show/$image_id");
        self::assertEquals(302, $page->code);

        self::get_page("post/list/md5:17fc89f372ed3636e28bd25cc7f3bac1/1");
        //self::assert_title(new PatternExpectation("/^Image \d+: data/"));
        //$this->click("Delete");
    }
}
