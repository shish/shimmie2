<?php

declare(strict_types=1);

namespace Shimmie2;

final class TagMapTest extends ShimmiePHPUnitTestCase
{
    /** @var string[] */
    private array $pages = ["map", "alphabetic", "popularity"];

    public function testTagList(): void
    {
        self::get_page('tags/map');
        self::assert_title('Tag List');

        self::get_page('tags/alphabetic');
        self::assert_title('Tag List');

        self::get_page('tags/popularity');
        self::assert_title('Tag List');

        # FIXME: test that these show the right stuff
    }

    public function testMinCount(): void
    {
        foreach ($this->pages as $page) {
            self::get_page("tags/$page", ["mincount" => "999999"]);
            self::assert_title("Tag List");

            self::get_page("tags/$page", ["mincount" => "1"]);
            self::assert_title("Tag List");

            self::get_page("tags/$page", ["mincount" => "0"]);
            self::assert_title("Tag List");

            self::get_page("tags/$page", ["mincount" => "-1"]);
            self::assert_title("Tag List");
        }
    }
}
