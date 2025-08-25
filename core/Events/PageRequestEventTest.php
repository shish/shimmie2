<?php

declare(strict_types=1);

namespace Shimmie2;

final class PageRequestEventTest extends ShimmiePHPUnitTestCase
{
    // Event::__toString() is only for debugging and nothing else tests it
    public function testToString(): void
    {
        $e = new PageRequestEvent("GET", "foo/bar");
        self::assertNotEmpty((string)$e);
    }

    public function testPageMatches(): void
    {
        $e = new PageRequestEvent("GET", "foo/bar");

        self::assertFalse($e->page_matches("foo"));
        self::assertFalse($e->page_matches("foo/qux"));
        self::assertTrue($e->page_matches("foo/bar"));
        self::assertFalse($e->page_matches("foo/bar/baz"));

        self::assertFalse($e->page_matches("{thing}"));

        self::assertTrue($e->page_matches("foo/{thing}"));
        self::assertEquals("bar", $e->get_arg('thing'));

        self::assertTrue($e->page_matches("{thing}/bar"));
        self::assertEquals("foo", $e->get_arg('thing'));
        self::assertFalse($e->page_matches("qux/{thing}"));
        self::assertFalse($e->page_matches("foo/{thing}/long"));
    }

    public function testPageMatchesPaged(): void
    {
        $e = new PageRequestEvent("GET", "foo/bar/4");

        self::assertFalse($e->page_matches("foo", paged: true));
        self::assertEquals(1, $e->get_iarg('page_num', 1));
        self::assertFalse($e->page_matches("foo/qux", paged: true));
        self::assertTrue($e->page_matches("foo/bar", paged: true));
        self::assertEquals(4, $e->get_iarg('page_num', 1));
        self::assertFalse($e->page_matches("foo/bar/baz", paged: true));

        self::assertFalse($e->page_matches("{thing}", paged: true));

        self::assertTrue($e->page_matches("foo/{thing}", paged: true));
        self::assertEquals("bar", $e->get_arg('thing'));
        self::assertEquals(4, $e->get_iarg('page_num', 1));

        self::assertTrue($e->page_matches("{thing}/bar", paged: true));
        self::assertEquals("foo", $e->get_arg('thing'));
        self::assertEquals(4, $e->get_iarg('page_num', 1));
        self::assertFalse($e->page_matches("qux/{thing}", paged: true));
        self::assertFalse($e->page_matches("foo/{thing}/long", paged: true));
    }

    public function testGetArgs(): void
    {
        $e = new PageRequestEvent("GET", "foo/bar/4/baz");

        self::assertTrue($e->page_matches("foo/bar/{a}/{b}"));
        self::assertEquals("4", $e->get_arg('a'));
        self::assertEquals("baz", $e->get_arg('b'));
        self::assertException(UserError::class, fn () => $e->get_arg('c'));

        self::assertEquals(4, $e->get_iarg('a'));
        self::assertException(UserError::class, fn () => $e->get_iarg('b'));
        self::assertException(UserError::class, fn () => $e->get_iarg('c'));
    }
}
