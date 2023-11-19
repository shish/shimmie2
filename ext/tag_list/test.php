<?php

declare(strict_types=1);

namespace Shimmie2;

class TagListTest extends ShimmiePHPUnitTestCase
{
    private array $pages = ["map", "alphabetic", "popularity", "categories"];

    public function testTagList()
    {
        $this->get_page('tags/map');
        $this->assert_title('Tag List');

        $this->get_page('tags/alphabetic');
        $this->assert_title('Tag List');

        $this->get_page('tags/popularity');
        $this->assert_title('Tag List');

        $this->get_page('tags/categories');
        $this->assert_title('Tag List');

        # FIXME: test that these show the right stuff
    }

    public function testMinCount()
    {
        foreach ($this->pages as $page) {
            $this->get_page("tags/$page", ["mincount" => 999999]);
            $this->assert_title("Tag List");

            $this->get_page("tags/$page", ["mincount" => 1]);
            $this->assert_title("Tag List");

            $this->get_page("tags/$page", ["mincount" => 0]);
            $this->assert_title("Tag List");

            $this->get_page("tags/$page", ["mincount" => -1]);
            $this->assert_title("Tag List");
        }
    }
}
