<?php

declare(strict_types=1);

namespace Shimmie2;

final class NetworkTest extends ShimmiePHPUnitTestCase
{
    public function test_get_session_ipv4(): void
    {
        $_SERVER['REMOTE_ADDR'] = "1.2.3.4";
        Ctx::$config->set(UserAccountsConfig::SESSION_HASH_MASK, "255.255.0.0");
        self::assertEquals("1.2.0.0", Network::get_session_ip());
    }

    public function test_get_session_ipv6(): void
    {
        $_SERVER['REMOTE_ADDR'] = "0102::1";
        Ctx::$config->set(UserAccountsConfig::SESSION_HASH_MASK, "255.255.0.0");
        self::assertEquals("1.2.0.0", Network::get_session_ip());
    }

    public function test_ip_in_range(): void
    {
        self::assertTrue(Network::ip_in_range("1.2.3.4", "1.2.0.0/16"));
        self::assertFalse(Network::ip_in_range("4.3.2.1", "1.2.0.0/16"));

        // A single IP should be interpreted as a /32
        self::assertTrue(Network::ip_in_range("1.2.3.4", "1.2.3.4"));
    }

    public function test_is_bot(): void
    {
        $_SERVER["HTTP_USER_AGENT"] = "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)";
        self::assertTrue(Network::is_bot());

        $_SERVER["HTTP_USER_AGENT"] = "Opera/9.80 (Windows NT 6.1; U; en) Presto/2.12.388 Version/12.16";
        self::assertFalse(Network::is_bot());
    }

    public function test_http_parse_headers(): void
    {
        $raw_headers = "
Content-Type: text/html
Content-Length: 1234
X-Forwarded-For: 1.2.3.4
";

        self::assertEquals([
            "Content-Type" => "text/html",
            "Content-Length" => "1234",
            "X-Forwarded-For" => "1.2.3.4",
        ], Network::http_parse_headers($raw_headers));
    }

    public function test_find_header(): void
    {
        $headers = [
            "Content-Type" => "text/html",
            "Content-Length" => "1234",
            "x-forwarded-for" => "1.2.3.4",
        ];
        self::assertEquals("text/html", Network::find_header($headers, "Content-Type"));
        self::assertEquals("1.2.3.4", Network::find_header($headers, "X-Forwarded-For"));
    }

}
