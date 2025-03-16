<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class NetworkTest extends TestCase
{
    public function test_get_session_ipv4(): void
    {
        $_SERVER['REMOTE_ADDR'] = "1.2.3.4";
        self::assertEquals(
            "1.2.0.0",
            Network::get_session_ip(new TestConfig([UserAccountsConfig::SESSION_HASH_MASK => "255.255.0.0"]))
        );
    }

    public function test_get_session_ipv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = "0102::1";
        self::assertEquals(
            "1.2.0.0",
            Network::get_session_ip(new TestConfig([UserAccountsConfig::SESSION_HASH_MASK => "255.255.0.0"]))
        );
    }

    public function test_ip_in_range(): void
    {
        self::assertTrue(Network::ip_in_range("1.2.3.4", "1.2.0.0/16"));
        self::assertFalse(Network::ip_in_range("4.3.2.1", "1.2.0.0/16"));

        // A single IP should be interpreted as a /32
        self::assertTrue(Network::ip_in_range("1.2.3.4", "1.2.3.4"));
    }
}
