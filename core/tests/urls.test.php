<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once "core/urls.php";

class UrlsTest extends TestCase
{
    public function test_make_link()
    {
        $this->assertEquals(
            "/test/foo",
            make_link("foo")
        );

        $this->assertEquals(
            "/test/foo",
            make_link("/foo")
        );
    }

    public function test_make_http()
    {
        // relative to shimmie install
        $this->assertEquals(
            "http://<cli command>/test/foo",
            make_http("foo")
        );

        // relative to web server
        $this->assertEquals(
            "http://<cli command>/foo",
            make_http("/foo")
        );

        // absolute
        $this->assertEquals(
            "https://foo.com",
            make_http("https://foo.com")
        );
    }
}
