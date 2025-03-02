<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function test_compare(): void
    {
        $this->assertFalse(Tag::compare(["foo"], ["bar"]));
        $this->assertFalse(Tag::compare(["foo"], ["foo", "bar"]));
        $this->assertTrue(Tag::compare([], []));
        $this->assertTrue(Tag::compare(["foo"], ["FoO"]));
        $this->assertTrue(Tag::compare(["foo", "bar"], ["bar", "FoO"]));
    }
}
