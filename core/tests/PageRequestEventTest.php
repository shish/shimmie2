<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class PageRequestEventTest extends TestCase
{
    public function testPageMatches(): void
    {
        $e = new PageRequestEvent("GET", "foo/bar", [], []);

        $this->assertFalse($e->page_matches("foo"));
        $this->assertFalse($e->page_matches("foo/qux"));
        $this->assertTrue($e->page_matches("foo/bar"));
        $this->assertFalse($e->page_matches("foo/bar/baz"));

        $this->assertFalse($e->page_matches("{thing}"));

        $this->assertTrue($e->page_matches("foo/{thing}"));
        $this->assertEquals("bar", $e->get_arg('thing'));

        $this->assertTrue($e->page_matches("{thing}/bar"));
        $this->assertEquals("foo", $e->get_arg('thing'));
        $this->assertFalse($e->page_matches("qux/{thing}"));
        $this->assertFalse($e->page_matches("foo/{thing}/long"));
    }

    public function testPageMatchesPaged(): void
    {
        $e = new PageRequestEvent("GET", "foo/bar/4", [], []);

        $this->assertFalse($e->page_matches("foo", paged: true));
        $this->assertEquals(1, $e->get_iarg('page_num', 1));
        $this->assertFalse($e->page_matches("foo/qux", paged: true));
        $this->assertTrue($e->page_matches("foo/bar", paged: true));
        $this->assertEquals(4, $e->get_iarg('page_num', 1));
        $this->assertFalse($e->page_matches("foo/bar/baz", paged: true));

        $this->assertFalse($e->page_matches("{thing}", paged: true));

        $this->assertTrue($e->page_matches("foo/{thing}", paged: true));
        $this->assertEquals("bar", $e->get_arg('thing'));
        $this->assertEquals(4, $e->get_iarg('page_num', 1));

        $this->assertTrue($e->page_matches("{thing}/bar", paged: true));
        $this->assertEquals("foo", $e->get_arg('thing'));
        $this->assertEquals(4, $e->get_iarg('page_num', 1));
        $this->assertFalse($e->page_matches("qux/{thing}", paged: true));
        $this->assertFalse($e->page_matches("foo/{thing}/long", paged: true));
    }
}
