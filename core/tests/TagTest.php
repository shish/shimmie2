<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

require_once "core/imageboard/tag.php";

class TagTest extends TestCase
{
    public function test_caret()
    {
        $this->assertEquals("foo", Tag::decaret("foo"));
        $this->assertEquals("foo?", Tag::decaret("foo^q"));
        $this->assertEquals("a^b/c\\d?e&f", Tag::decaret("a^^b^sc^bd^qe^af"));
    }

    public function test_decaret()
    {
        $this->assertEquals("foo", Tag::caret("foo"));
        $this->assertEquals("foo^q", Tag::caret("foo?"));
        $this->assertEquals("a^^b^sc^bd^qe^af", Tag::caret("a^b/c\\d?e&f"));
    }

    public function test_compare()
    {
        $this->assertFalse(Tag::compare(["foo"], ["bar"]));
        $this->assertFalse(Tag::compare(["foo"], ["foo", "bar"]));
        $this->assertTrue(Tag::compare([], []));
        $this->assertTrue(Tag::compare(["foo"], ["FoO"]));
        $this->assertTrue(Tag::compare(["foo", "bar"], ["bar", "FoO"]));
    }
}
