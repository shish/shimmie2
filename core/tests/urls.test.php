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

    public function test_modify_url()
    {
        $this->assertEquals(
            "/foo/bar?a=3&b=2",
            modify_url("/foo/bar?a=1&b=2", ["a"=>"3"])
        );

        $this->assertEquals(
            "https://blah.com/foo/bar?b=2",
            modify_url("https://blah.com/foo/bar?a=1&b=2", ["a"=>null])
        );

        $this->assertEquals(
            "/foo/bar",
            modify_url("/foo/bar?a=1&b=2", ["a"=>null, "b"=>null])
        );
    }
}
