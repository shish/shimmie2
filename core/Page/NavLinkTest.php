<?php

declare(strict_types=1);

namespace Shimmie2;

class NavLinkTest extends ShimmiePHPUnitTestCase
{
    public function test_nav_link(): void
    {
        // query matches link
        $l = new NavLink(make_link("foo"), "Foo", _query: "foo");
        $this->assertTrue($l->active);

        // query does not match link
        $l = new NavLink(make_link("foo"), "Foo", _query: "bar");
        $this->assertFalse($l->active);

        // query starts with link
        $l = new NavLink(make_link("foo"), "Foo", _query: "foo/bar");
        $this->assertFalse($l->active);

        // query matches matches
        $l = new NavLink(make_link("foo"), "Foo", ["bar"], _query: "bar");
        $this->assertTrue($l->active);

        // query does not match matches
        $l = new NavLink(make_link("foo"), "Foo", ["bar"], _query: "qux");
        $this->assertFalse($l->active);

        // query starts with matches
        $l = new NavLink(make_link("foo"), "Foo", ["foo"], _query: "foo/bar");
        $this->assertTrue($l->active);
    }
}
