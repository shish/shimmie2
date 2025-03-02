<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class NetworkTest extends TestCase
{
    public function test_get_session_ipv4(): void
    {
        $_SERVER['REMOTE_ADDR'] = "1.2.3.4";
        $this->assertEquals(
            "1.2.0.0",
            Network::get_session_ip(new TestConfig([UserAccountsConfig::SESSION_HASH_MASK => "255.255.0.0"]))
        );
    }

    public function test_get_session_ipv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = "0102::1";
        $this->assertEquals(
            "1.2.0.0",
            Network::get_session_ip(new TestConfig([UserAccountsConfig::SESSION_HASH_MASK => "255.255.0.0"]))
        );
    }

    public function test_ip_in_range(): void
    {
        $this->assertTrue(Network::ip_in_range("1.2.3.4", "1.2.0.0/16"));
        $this->assertFalse(Network::ip_in_range("4.3.2.1", "1.2.0.0/16"));

        // A single IP should be interpreted as a /32
        $this->assertTrue(Network::ip_in_range("1.2.3.4", "1.2.3.4"));
    }
}
