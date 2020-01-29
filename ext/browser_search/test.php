<?php declare(strict_types=1);
class BrowserSearchTest extends ShimmiePHPUnitTestCase
{
    public function testBasic()
    {
        $page = $this->get_page("browser_search/please_dont_use_this_tag_as_it_would_break_stuff__search.xml");
        $this->assertEquals(200, $page->code);

        $page = $this->get_page("browser_search/test");
        $this->assertEquals(200, $page->code);
    }
}
