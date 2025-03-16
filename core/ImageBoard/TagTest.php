<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class TagTest extends TestCase
{
    public function test_compare(): void
    {
        self::assertFalse(Tag::compare(["foo"], ["bar"]));
        self::assertFalse(Tag::compare(["foo"], ["foo", "bar"]));
        self::assertTrue(Tag::compare([], []));
        self::assertTrue(Tag::compare(["foo"], ["FoO"]));
        self::assertTrue(Tag::compare(["foo", "bar"], ["bar", "FoO"]));
    }
}
