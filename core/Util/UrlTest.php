<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

final class UrlTest extends TestCase
{
    public function test_query_joiner(): void
    {
        global $config;

        $config->set_bool(SetupConfig::NICE_URLS, true);
        self::assertEquals(
            "/test/foo?a=1&b=2",
            (string)make_link("foo", ["a" => "1", "b" => "2"])
        );

        $config->set_bool(SetupConfig::NICE_URLS, false);
        self::assertEquals(
            "/test/index.php?q=foo&a=1&b=2",
            (string)make_link("foo", ["a" => "1", "b" => "2"])
        );
    }

    public function test_get_base_href(): void
    {
        // PHP_SELF should point to "the currently executing script
        // relative to the document root"
        self::assertEquals("", Url::base(["PHP_SELF" => "/index.php"]));
        self::assertEquals("/mydir", Url::base(["PHP_SELF" => "/mydir/index.php"]));

        // SCRIPT_FILENAME should point to "the absolute pathname of
        // the currently executing script" and DOCUMENT_ROOT should
        // point to "the document root directory under which the
        // current script is executing"
        self::assertEquals("", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/index.php",
            "DOCUMENT_ROOT" => "/var/www/html",
        ]), "root directory");
        self::assertEquals("/mydir", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/mydir/index.php",
            "DOCUMENT_ROOT" => "/var/www/html",
        ]), "subdirectory");
        self::assertEquals("", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/index.php",
            "DOCUMENT_ROOT" => "/var/www/html/",
        ]), "trailing slash in DOCUMENT_ROOT root should be ignored");
        self::assertEquals("/mydir", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/mydir/index.php",
            "DOCUMENT_ROOT" => "/var/www/html/",
        ]), "trailing slash in DOCUMENT_ROOT subdir should be ignored");
    }

    public function test_is_https_enabled(): void
    {
        self::assertFalse(Url::is_https_enabled(), "HTTPS should be disabled by default");

        $_SERVER['HTTPS'] = "on";
        self::assertTrue(Url::is_https_enabled(), "HTTPS should be enabled when set to 'on'");
        unset($_SERVER['HTTPS']);
    }

    public function test_arg_encode(): void
    {
        global $config;

        self::assertEquals(
            "/test/foo?api_key=Something+%2F%2F+not-friendly",
            (string)make_link("foo", ["api_key" => "Something // not-friendly"])
        );
    }

    public function test_arg_decode(): void
    {
        global $config;

        self::assertEquals(
            ["api_key" => "Something // not-friendly"],
            Url::parse("/test/foo?api_key=Something+%2F%2F+not-friendly")->getQueryArray()
        );
        /*
        self::assertEquals(
            ["q" => "post/list/AC%2FDC/1"],
            Url::parse("/test/foo?q=post/list/AC%2FDC/1")->query
        );
        */
    }

    public function test_path_decode(): void
    {
        self::assertEquals(
            "/post/list/AC%2FDC/1",
            Url::parse("/post/list/AC%2FDC/1")->getPath()
        );
    }

    public function test_referer_or(): void
    {
        // No referrer, go to default
        unset($_SERVER['HTTP_REFERER']);
        self::assertEquals(
            "/test/foo",
            Url::referer_or(make_link("foo"))
        );

        // Referrer set, go to referrer
        $_SERVER['HTTP_REFERER'] = "/cake";
        self::assertEquals(
            "/cake",
            Url::referer_or(make_link("foo"))
        );

        // Referrer set but ignored, go to default
        $_SERVER['HTTP_REFERER'] = "/cake";
        self::assertEquals(
            "/test/foo",
            Url::referer_or(make_link("foo"), ["cake"])
        );
    }

    public function test_withModifiedQuery(): void
    {
        global $config;

        // add an arg
        self::assertEquals(
            "/foo/bar?modified=true",
            Url::parse("/foo/bar")->withModifiedQuery(["modified" => "true"])
        );
        // replace an arg
        self::assertEquals(
            "/foo/bar?modified=true",
            Url::parse("/foo/bar?modified=false")->withModifiedQuery(["modified" => "true"])
        );
        // remove an arg
        self::assertEquals(
            "/foo/bar",
            Url::parse("/foo/bar?modified=false")->withModifiedQuery(["modified" => null])
        );
        // leave other args alone
        self::assertEquals(
            "/foo/bar?existing=cake&modified=true",
            Url::parse("/foo/bar?existing=cake&modified=false")->withModifiedQuery(["modified" => "true"])
        );
    }

    // make_link is a widely-used alias for `new Url(page: $page)`
    #[Depends("test_query_joiner")]
    public function test_make_link(): void
    {
        global $config;
        foreach ([true, false] as $nice_urls) {
            $config->set_bool(SetupConfig::NICE_URLS, $nice_urls);

            // basic
            self::assertEquals(
                $nice_urls ? "/test/foo" : "/test/index.php?q=foo",
                make_link("foo")
            );

            // query
            self::assertEquals(
                $nice_urls ? "/test/foo?a=1&b=2" : "/test/index.php?q=foo&a=1&b=2",
                (string)make_link("foo", ["a" => "1", "b" => "2"])
            );

            // hash
            self::assertEquals(
                $nice_urls ? "/test/foo#cake" : "/test/index.php?q=foo#cake",
                make_link("foo", null, "cake")
            );

            // query + hash
            self::assertEquals(
                $nice_urls ? "/test/foo?a=1&b=2#cake" : "/test/index.php?q=foo&a=1&b=2#cake",
                make_link("foo", ["a" => "1", "b" => "2"], "cake")
            );
        }
    }

    public function test_asAbsolute(): void
    {
        self::assertEquals(
            "http://cli-command/foo/bar",
            (new Url(path: "/foo/bar"))->asAbsolute()
        );
    }

    public function tearDown(): void
    {
        global $config;
        $config->set_bool(SetupConfig::NICE_URLS, true);
        parent::tearDown();
    }
}
