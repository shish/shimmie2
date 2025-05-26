<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\Attributes\{DataProvider, Depends};
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    /**
     * @return array<array{boolean}>
     */
    public static function niceurl_options(): array
    {
        return [[true], [false]];
    }

    public static function assertUrlEquals(string $a, Url $b, string $message = ""): void
    {
        $cmp = "Expected: $a\nActual:   $b";
        parent::assertEquals($a, $b, empty($message) ? $cmp : $message);
    }

    public function test_parse(): void
    {
        self::assertEquals(
            new Url(path: "/index.php", query: new QueryArray(["q" => "thumb/2/thumb.jpg"])),
            Url::parse("/index.php?q=thumb/2/thumb.jpg")
        );
    }

    #[DataProvider("niceurl_options")]
    public function test_toString(bool $niceurls): void
    {
        // since this uses path: rather than page:, niceurls should have no effect
        Ctx::$config->set(SetupConfig::NICE_URLS, $niceurls);
        self::assertUrlEquals(
            "/index.php?q=thumb%2F2%2Fthumb.jpg",
            new Url(path: "/index.php", query: new QueryArray(["q" => "thumb/2/thumb.jpg"])),
        );
    }

    public function test_query_joiner(): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        self::assertUrlEquals(
            "/test/foo?a=1&b=2",
            make_link("foo", ["a" => "1", "b" => "2"])
        );

        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        self::assertUrlEquals(
            "/test/index.php?a=1&b=2&q=foo",
            make_link("foo", ["a" => "1", "b" => "2"])
        );
    }

    public function test_get_base_href(): void
    {
        // PHP_SELF should point to "the currently executing script
        // relative to the document root"
        self::assertUrlEquals("", Url::base(["PHP_SELF" => "/index.php"]));
        self::assertUrlEquals("/mydir", Url::base(["PHP_SELF" => "/mydir/index.php"]));
        self::assertUrlEquals("/my%20dir", Url::base(["PHP_SELF" => "/my dir/index.php"]));

        // SCRIPT_FILENAME should point to "the absolute pathname of
        // the currently executing script" and DOCUMENT_ROOT should
        // point to "the document root directory under which the
        // current script is executing"
        self::assertUrlEquals("", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/index.php",
            "DOCUMENT_ROOT" => "/var/www/html",
        ]), "root directory");
        self::assertUrlEquals("/mydir", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/mydir/index.php",
            "DOCUMENT_ROOT" => "/var/www/html",
        ]), "subdirectory");
        self::assertUrlEquals("", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/index.php",
            "DOCUMENT_ROOT" => "/var/www/html/",
        ]), "trailing slash in DOCUMENT_ROOT root should be ignored");
        self::assertUrlEquals("/mydir", Url::base([
            "PHP_SELF" => "<invalid>",
            "SCRIPT_FILENAME" => "/var/www/html/mydir/index.php",
            "DOCUMENT_ROOT" => "/var/www/html/",
        ]), "trailing slash in DOCUMENT_ROOT subdir should be ignored");
    }

    /**
     * An integration test for
     * - search_link()
     *   - make_link_str()
     * - _get_query()
     * - get_search_terms()
     */
    #[Depends("test_search_link")]
    #[DataProvider("niceurl_options")]
    public function test_get_search_terms_from_search_link(bool $nice_urls): void
    {
        /**
         * @param array<string> $vars
         * @return array<string>
         */
        $gst = function (array $terms): array {
            $pre = new PageRequestEvent("GET", _get_query((string)search_link($terms)), new QueryArray([]), new QueryArray([]));
            $pre->page_matches("post/list/{search}/{page}");
            return Tag::explode($pre->get_arg('search'));
        };

        Ctx::$config->set(SetupConfig::NICE_URLS, $nice_urls);

        self::assertEquals(
            ["bar", "foo"],
            $gst(["foo", "bar"])
        );
        self::assertEquals(
            ["AC/DC"],
            $gst(["AC/DC"])
        );
        self::assertEquals(
            ["cat*", "rating=?"],
            $gst(["rating=?", "cat*"]),
        );
    }

    #[DataProvider("niceurl_options")]
    public function test_search_link(bool $nice_urls): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, $nice_urls);

        self::assertUrlEquals(
            $nice_urls ? "/test/post/list/bar%20foo/1" : "/test/index.php?q=post%2Flist%2Fbar%2520foo%2F1",
            search_link(["foo", "bar"])
        );
        self::assertUrlEquals(
            $nice_urls ? "/test/post/list/AC%2FDC/1" : "/test/index.php?q=post%2Flist%2FAC%252FDC%2F1",
            search_link(["AC/DC"])
        );
        self::assertUrlEquals(
            $nice_urls ? "/test/post/list/cat%2A%20rating%3D%3F/1" : "/test/index.php?q=post%2Flist%2Fcat%252A%2520rating%253D%253F%2F1",
            search_link(["rating=?", "cat*"])
        );
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
        self::assertUrlEquals(
            "/test/foo?api_key=Something+%2F%2F+not-friendly",
            make_link("foo", ["api_key" => "Something // not-friendly"])
        );
    }

    public function test_arg_decode(): void
    {
        self::assertEquals(
            new QueryArray(["api_key" => "Something // not-friendly"]),
            Url::parse("/test/foo?api_key=Something+%2F%2F+not-friendly")->getQueryArray()
        );
        /*
        self::assertUrlEquals(
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
        self::assertUrlEquals(
            "/test/foo",
            Url::referer_or(make_link("foo"))
        );

        // Referrer set, go to referrer
        $_SERVER['HTTP_REFERER'] = "/cake";
        self::assertUrlEquals(
            "/cake",
            Url::referer_or(make_link("foo"))
        );

        // Referrer set but ignored, go to default
        $_SERVER['HTTP_REFERER'] = "/cake";
        self::assertUrlEquals(
            "/test/foo",
            Url::referer_or(make_link("foo"), ["cake"])
        );
    }

    public function test_withModifiedQuery(): void
    {
        // add an arg
        self::assertUrlEquals(
            "/foo/bar?modified=true",
            Url::parse("/foo/bar")->withModifiedQuery(["modified" => "true"])
        );
        // replace an arg
        self::assertUrlEquals(
            "/foo/bar?modified=true",
            Url::parse("/foo/bar?modified=false")->withModifiedQuery(["modified" => "true"])
        );
        // remove an arg
        self::assertUrlEquals(
            "/foo/bar",
            Url::parse("/foo/bar?modified=false")->withModifiedQuery(["modified" => null])
        );
        // leave other args alone
        self::assertUrlEquals(
            "/foo/bar?existing=cake&modified=true",
            Url::parse("/foo/bar?existing=cake&modified=false")->withModifiedQuery(["modified" => "true"])
        );
    }

    // make_link is a widely-used alias for `new Url(page: $page)`
    #[Depends("test_query_joiner")]
    #[DataProvider("niceurl_options")]
    public function test_make_link(bool $nice_urls): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, $nice_urls);

        // basic
        self::assertUrlEquals(
            $nice_urls ? "/test/foo" : "/test/index.php?q=foo",
            make_link("foo")
        );

        // query
        self::assertUrlEquals(
            $nice_urls ? "/test/foo?a=1&b=2" : "/test/index.php?a=1&b=2&q=foo",
            make_link("foo", ["a" => "1", "b" => "2"])
        );

        // hash
        self::assertUrlEquals(
            $nice_urls ? "/test/foo#cake" : "/test/index.php?q=foo#cake",
            make_link("foo", null, "cake")
        );

        // query + hash
        self::assertUrlEquals(
            $nice_urls ? "/test/foo?a=1&b=2#cake" : "/test/index.php?a=1&b=2&q=foo#cake",
            make_link("foo", ["a" => "1", "b" => "2"], "cake")
        );
    }

    #[Depends("test_is_https_enabled")]
    public function test_asAbsolute(): void
    {
        self::assertUrlEquals(
            "http://cli-command/foo/bar",
            (new Url(path: "/foo/bar"))->asAbsolute()
        );
        self::assertUrlEquals(
            "https://already-absolute.com/foo/bar",
            (new Url(scheme: "https", host: "already-absolute.com", path: "/foo/bar"))->asAbsolute()
        );
    }

    public function tearDown(): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        parent::tearDown();
    }
}
