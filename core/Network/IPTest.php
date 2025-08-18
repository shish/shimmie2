<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class IPTest extends TestCase
{
    public function test_rangev4_parse(): void
    {
        $r = IPRange::parse("1.2.3.4");
        self::assertInstanceOf(IPRangeV4::class, $r);
        self::assertEquals("1.2.3.4", (string)$r->ip);
        self::assertEquals(32, $r->mask);
    }

    public function test_rangev6_parse(): void
    {
        $r = IPRange::parse("1234:5678:9abc:def0:1234:5678:9abc:def0/64");
        self::assertInstanceOf(IPRangeV6::class, $r);
        self::assertEquals("1234:5678:9abc:def0:1234:5678:9abc:def0", (string)$r->ip);
        self::assertEquals(64, $r->mask);
    }

    public function test_rangev4_contains(): void
    {
        $range = IPRange::parse("1.2.0.0/16");
        $ip1234 = IPAddress::parse("1.2.3.4");
        $ip4321 = IPAddress::parse("4.3.2.1");

        self::assertTrue($range->contains($ip1234));
        self::assertFalse($range->contains($ip4321));
    }

    public function test_rangev6_contains(): void
    {
        $range = IPRange::parse("12:3:4::/32");
        $ip1234 = IPAddress::parse("12:3:4::1");
        $ip4321 = IPAddress::parse("43:2:1::2");

        self::assertTrue($range->contains($ip1234));
        self::assertFalse($range->contains($ip4321));
    }
}
