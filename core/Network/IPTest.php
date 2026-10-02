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
        self::assertSame("1.2.3.4", (string)$r->ip);
        self::assertSame(32, $r->mask);
    }

    public function test_rangev6_parse(): void
    {
        $r = IPRange::parse("1234:5678:9abc:def0:1234:5678:9abc:def0/64");
        self::assertInstanceOf(IPRangeV6::class, $r);
        self::assertSame("1234:5678:9abc:def0:1234:5678:9abc:def0", (string)$r->ip);
        self::assertSame(64, $r->mask);
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

    public function test_ipv4_is_private(): void
    {
        $loopback = IPAddress::parse("127.0.0.1");
        self::assertTrue($loopback->is_localhost());
        self::assertTrue($loopback->is_private());

        $private_10 = IPAddress::parse("10.0.0.1");
        self::assertTrue($private_10->is_private());

        $link_local = IPAddress::parse("169.254.1.1");
        self::assertTrue($link_local->is_private());

        $public_google = IPAddress::parse("8.8.8.8");
        $public_cloudflare = IPAddress::parse("1.1.1.1");
        self::assertFalse($public_google->is_private());
        self::assertFalse($public_cloudflare->is_private());
    }

    public function test_ipv6_is_private_loopback(): void
    {
        $loopback_short = IPAddress::parse("::1");
        $loopback_long = IPAddress::parse("0:0:0:0:0:0:0:1");
        self::assertTrue($loopback_short->is_localhost());
        self::assertTrue($loopback_long->is_localhost());
        self::assertTrue($loopback_short->is_private());
        self::assertTrue($loopback_long->is_private());

        $link_local = IPAddress::parse("fe80::1");
        self::assertTrue($link_local->is_private());

        $unique_fc = IPAddress::parse("fc00::1");
        $unique_fd = IPAddress::parse("fd00::1");
        self::assertTrue($unique_fc->is_private());
        self::assertTrue($unique_fd->is_private());

        $public_cloudflare = IPAddress::parse("2606:4700:4700::1111");
        $public_google = IPAddress::parse("2001:4860:4860::8888");
        self::assertFalse($public_cloudflare->is_private());
        self::assertFalse($public_google->is_private());
    }
}
