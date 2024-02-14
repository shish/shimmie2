<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

require_once "core/urls.php";

class UrlsTest extends TestCase
{
    /**
     * An integration test for
     * - search_link()
     *   - make_link()
     * - _get_query()
     * - get_search_terms()
     */
    #[Depends("test_search_link")]
    public function test_get_search_terms_from_search_link(): void
    {
        /**
         * @param array<string> $vars
         * @return array<string>
         */
        $gst = function (array $terms): array {
            $pre = new PageRequestEvent("GET", _get_query(search_link($terms)), [], []);
            $pre->page_matches("post/list/{search}/{page}");
            return Tag::explode($pre->get_arg('search'));
        };

        global $config;
        foreach([true, false] as $nice_urls) {
            $config->set_bool(SetupConfig::NICE_URLS, $nice_urls);

            $this->assertEquals(
                ["bar", "foo"],
                $gst(["foo", "bar"])
            );
            $this->assertEquals(
                ["AC/DC"],
                $gst(["AC/DC"])
            );
            $this->assertEquals(
                ["cat*", "rating=?"],
                $gst(["rating=?", "cat*"]),
            );
        }
    }

    #[Depends("test_get_base_href")]
    public function test_make_link(): void
    {
        global $config;
        foreach([true, false] as $nice_urls) {
            $config->set_bool(SetupConfig::NICE_URLS, $nice_urls);

            // basic
            $this->assertEquals(
                $nice_urls ? "/test/foo" : "/test/index.php?q=foo",
                make_link("foo")
            );

            // remove leading slash from path
            $this->assertEquals(
                $nice_urls ? "/test/foo" : "/test/index.php?q=foo",
                make_link("/foo")
            );

            // query
            $this->assertEquals(
                $nice_urls ? "/test/foo?a=1&b=2" : "/test/index.php?q=foo&a=1&b=2",
                make_link("foo", "a=1&b=2")
            );

            // hash
            $this->assertEquals(
                $nice_urls ? "/test/foo#cake" : "/test/index.php?q=foo#cake",
                make_link("foo", null, "cake")
            );

            // query + hash
            $this->assertEquals(
                $nice_urls ? "/test/foo?a=1&b=2#cake" : "/test/index.php?q=foo&a=1&b=2#cake",
                make_link("foo", "a=1&b=2", "cake")
            );
        }
    }

    #[Depends("test_make_link")]
    public function test_search_link(): void
    {
        global $config;
        foreach([true, false] as $nice_urls) {
            $config->set_bool(SetupConfig::NICE_URLS, $nice_urls);

            $this->assertEquals(
                $nice_urls ? "/test/post/list/bar%20foo/1" : "/test/index.php?q=post/list/bar%20foo/1",
                search_link(["foo", "bar"])
            );
            $this->assertEquals(
                $nice_urls ? "/test/post/list/AC%2FDC/1" : "/test/index.php?q=post/list/AC%2FDC/1",
                search_link(["AC/DC"])
            );
            $this->assertEquals(
                $nice_urls ? "/test/post/list/cat%2A%20rating%3D%3F/1" : "/test/index.php?q=post/list/cat%2A%20rating%3D%3F/1",
                search_link(["rating=?", "cat*"])
            );
        }
    }

    #[Depends("test_get_base_href")]
    public function test_get_query(): void
    {
        // just validating an assumption that this test relies upon
        $this->assertEquals(get_base_href(), "/test");

        $this->assertEquals(
            "tasty/cake",
            _get_query("/test/tasty/cake"),
            'http://$SERVER/$INSTALL_DIR/$PATH should return $PATH'
        );
        $this->assertEquals(
            "tasty/cake",
            _get_query("/test/index.php?q=tasty/cake"),
            'http://$SERVER/$INSTALL_DIR/index.php?q=$PATH should return $PATH'
        );

        $this->assertEquals(
            "tasty/cake%20pie",
            _get_query("/test/index.php?q=tasty/cake%20pie"),
            'URL encoded paths should be left alone'
        );
        $this->assertEquals(
            "tasty/cake%20pie",
            _get_query("/test/tasty/cake%20pie"),
            'URL encoded queries should be left alone'
        );

        $this->assertEquals(
            "",
            _get_query("/test/"),
            'If just viewing install directory, should return /'
        );
        $this->assertEquals(
            "",
            _get_query("/test/index.php"),
            'If just viewing index.php, should return /'
        );

        $this->assertEquals(
            "post/list/tasty%2Fcake/1",
            _get_query("/test/post/list/tasty%2Fcake/1"),
            'URL encoded niceurls should be left alone, even encoded slashes'
        );
        $this->assertEquals(
            "post/list/tasty%2Fcake/1",
            _get_query("/test/index.php?q=post/list/tasty%2Fcake/1"),
            'URL encoded uglyurls should be left alone, even encoded slashes'
        );
    }

    public function test_is_https_enabled(): void
    {
        $this->assertFalse(is_https_enabled(), "HTTPS should be disabled by default");

        $_SERVER['HTTPS'] = "on";
        $this->assertTrue(is_https_enabled(), "HTTPS should be enabled when set to 'on'");
        unset($_SERVER['HTTPS']);
    }

    public function test_get_base_href(): void
    {
        // PHP_SELF should point to "the currently executing script
        // relative to the document root"
        $this->assertEquals("", get_base_href(["PHP_SELF" => "/index.php"]));
        $this->assertEquals("/mydir", get_base_href(["PHP_SELF" => "/mydir/index.php"]));

        // SCRIPT_FILENAME should point to "the absolute pathname of
        // the currently executing script" and DOCUMENT_ROOT should
        // point to "the document root directory under which the
        // current script is executing"
        $this->assertEquals("", get_base_href([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/index.php",
            "DOCUMENT_ROOT" => "/var/www/html",
        ]), "root directory");
        $this->assertEquals("/mydir", get_base_href([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/mydir/index.php",
            "DOCUMENT_ROOT" => "/var/www/html",
        ]), "subdirectory");
        $this->assertEquals("", get_base_href([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/index.php",
            "DOCUMENT_ROOT" => "/var/www/html/",
        ]), "trailing slash in DOCUMENT_ROOT root should be ignored");
        $this->assertEquals("/mydir", get_base_href([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/mydir/index.php",
            "DOCUMENT_ROOT" => "/var/www/html/",
        ]), "trailing slash in DOCUMENT_ROOT subdir should be ignored");
    }

    #[Depends("test_is_https_enabled")]
    #[Depends("test_get_base_href")]
    public function test_make_http(): void
    {
        $this->assertEquals(
            "http://cli-command/test/foo",
            make_http("foo"),
            "relative to shimmie root"
        );
        $this->assertEquals(
            "http://cli-command/foo",
            make_http("/foo"),
            "relative to web server"
        );
        $this->assertEquals(
            "https://foo.com",
            make_http("https://foo.com"),
            "absolute URL should be left alone"
        );
    }

    public function test_modify_url(): void
    {
        $this->assertEquals(
            "/foo/bar?a=3&b=2",
            modify_url("/foo/bar?a=1&b=2", ["a" => "3"])
        );

        $this->assertEquals(
            "https://blah.com/foo/bar?b=2",
            modify_url("https://blah.com/foo/bar?a=1&b=2", ["a" => null])
        );

        $this->assertEquals(
            "/foo/bar",
            modify_url("/foo/bar?a=1&b=2", ["a" => null, "b" => null])
        );
    }

    public function test_referer_or(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        $this->assertEquals(
            "foo",
            referer_or("foo")
        );

        $_SERVER['HTTP_REFERER'] = "cake";
        $this->assertEquals(
            "cake",
            referer_or("foo")
        );

        $_SERVER['HTTP_REFERER'] = "cake";
        $this->assertEquals(
            "foo",
            referer_or("foo", ["cake"])
        );
    }

    public function tearDown(): void
    {
        global $config;
        $config->set_bool(SetupConfig::NICE_URLS, true);
        parent::tearDown();
    }
}
