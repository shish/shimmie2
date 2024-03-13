<?php

declare(strict_types=1);

namespace Shimmie2;

class TagListTest extends ShimmiePHPUnitTestCase
{
    /** @var string[] */
    private array $pages = ["map", "alphabetic", "popularity"];

    public function testTagList(): void
    {
        $this->get_page('tags/map');
        $this->assert_title('Tag List');

        $this->get_page('tags/alphabetic');
        $this->assert_title('Tag List');

        $this->get_page('tags/popularity');
        $this->assert_title('Tag List');

        # FIXME: test that these show the right stuff
    }

    public function testMinCount(): void
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
