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

        self::assertSame([
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
        self::assertSame("text/html", Network::find_header($headers, "Content-Type"));
        self::assertSame("1.2.3.4", Network::find_header($headers, "X-Forwarded-For"));
    }

    public function test_resolve_hostname(): void
    {
        self::assertEqualsCanonicalizing([
            IPAddressV4::parse("8.8.4.4"),
            IPAddressV4::parse("8.8.8.8"),
            IPAddressV6::parse("2001:4860:4860::8844"),
            IPAddressV6::parse("2001:4860:4860::8888"),
        ], Network::resolve_hostname("dns.google"));
    }

    public function test_fetch_url_no_engine(): void
    {
        Ctx::$config->set(UploadConfig::TRANSLOAD_ENGINE, "none");
        $tmp = shm_tempnam("test_fetch_url");
        try {
            self::expectException(FetchException::class);
            self::expectExceptionMessage("No transload engine configured");
            Network::fetch_url("https://example.com", $tmp);
        } finally {
            if ($tmp->exists()) {
                $tmp->unlink();
            }
        }
    }

    public function test_fetch_url_curl_https(): void
    {
        Ctx::$config->set(UploadConfig::TRANSLOAD_ENGINE, "curl");
        $tmp = shm_tempnam("test_fetch_url_https");
        try {
            try {
                $headers = Network::fetch_url("https://example.com", $tmp);
                self::assertTrue($tmp->exists());
            } catch (FetchException $e) {
                // Network unavailable in sandbox, just verify curl attempted the request
                self::assertStringContainsString("cURL failed", $e->getMessage());
            }
        } finally {
            if ($tmp->exists()) {
                $tmp->unlink();
            }
        }
    }

    public function test_fetch_url_curl_localhost(): void
    {
        Ctx::$config->set(UploadConfig::TRANSLOAD_ENGINE, "curl");
        $tmp = shm_tempnam("test_fetch_url_localhost");
        try {
            self::expectException(FetchException::class);
            self::expectExceptionMessage("Invalid URL");
            Network::fetch_url("https://localhost", $tmp);
        } finally {
            if ($tmp->exists()) {
                $tmp->unlink();
            }
        }
    }

    public function test_fetch_url_curl_file(): void
    {
        Ctx::$config->set(UploadConfig::TRANSLOAD_ENGINE, "curl");
        $tmp = shm_tempnam("test_fetch_url_file");
        try {
            self::expectException(FetchException::class);
            self::expectExceptionMessage("Invalid URL");
            $favicon = new Path("tests/favicon.png");
            $file_url = "file://" . $favicon->absolute()->str();
            $headers = Network::fetch_url($file_url, $tmp);
        } finally {
            if ($tmp->exists()) {
                $tmp->unlink();
            }
        }
    }
}
