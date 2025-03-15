<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

final class UtilTest extends TestCase
{
    public function test_get_theme(): void
    {
        self::assertEquals("default", get_theme());
    }

    public function test_get_memory_limit(): void
    {
        self::assertGreaterThan(0, get_memory_limit());
    }

    public function test_check_gd_version(): void
    {
        self::assertGreaterThanOrEqual(0, check_gd_version());
    }

    public function test_check_im_version(): void
    {
        self::assertGreaterThanOrEqual(0, check_im_version());
    }

    public function test_human_filesize(): void
    {
        self::assertEquals("123.00B", human_filesize(123));
        self::assertEquals("123B", human_filesize(123, 0));
        self::assertEquals("120.56KB", human_filesize(123456));
    }

    public function test_generate_key(): void
    {
        self::assertEquals(20, strlen(generate_key()));
    }

    public function test_contact_link(): void
    {
        self::assertEquals(
            "mailto:asdf@example.com",
            contact_link("asdf@example.com")
        );
        self::assertEquals(
            "http://example.com",
            contact_link("http://example.com")
        );
        self::assertEquals(
            "https://foo.com/bar",
            contact_link("foo.com/bar")
        );
        self::assertEquals(
            "john",
            contact_link("john")
        );
    }

    public function test_get_user(): void
    {
        // TODO: HTTP_AUTHORIZATION
        // TODO: cookie user + session
        // fallback to anonymous
        self::assertEquals(
            "Anonymous",
            _get_user()->name
        );
    }

    /**
     * An integration test for
     * - search_link()
     *   - make_link_str()
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
            $pre = new PageRequestEvent("GET", _get_query((string)search_link($terms)), [], []);
            $pre->page_matches("post/list/{search}/{page}");
            return Tag::explode($pre->get_arg('search'));
        };

        global $config;
        foreach ([true, false] as $nice_urls) {
            $config->set_bool(SetupConfig::NICE_URLS, $nice_urls);

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
    }

    public function test_search_link(): void
    {
        global $config;
        foreach ([true, false] as $nice_urls) {
            $config->set_bool(SetupConfig::NICE_URLS, $nice_urls);

            self::assertEquals(
                $nice_urls ? "/test/post/list/bar%20foo/1" : "/test/index.php?q=post/list/bar%20foo/1",
                search_link(["foo", "bar"])
            );
            self::assertEquals(
                $nice_urls ? "/test/post/list/AC%2FDC/1" : "/test/index.php?q=post/list/AC%2FDC/1",
                search_link(["AC/DC"])
            );
            self::assertEquals(
                $nice_urls ? "/test/post/list/cat%2A%20rating%3D%3F/1" : "/test/index.php?q=post/list/cat%2A%20rating%3D%3F/1",
                search_link(["rating=?", "cat*"])
            );
        }
    }

    public function test_get_query(): void
    {
        // just validating an assumption that this test relies upon
        self::assertEquals(Url::base(), "/test");

        self::assertEquals(
            "tasty/cake",
            _get_query("/test/tasty/cake"),
            'http://$SERVER/$INSTALL_DIR/$PATH should return $PATH'
        );
        self::assertEquals(
            "tasty/cake",
            _get_query("/test/index.php?q=tasty/cake"),
            'http://$SERVER/$INSTALL_DIR/index.php?q=$PATH should return $PATH'
        );

        // even when we are /test/... publicly, and generating /test/... URLs,
        // we should still be able to handle URLs at the root because that's
        // what apache sends us when it is reverse-proxying a subdirectory
        self::assertEquals(
            "tasty/cake",
            _get_query("/tasty/cake"),
            'http://$SERVER/$INSTALL_DIR/$PATH should return $PATH'
        );
        self::assertEquals(
            "tasty/cake",
            _get_query("/index.php?q=tasty/cake"),
            'http://$SERVER/$INSTALL_DIR/index.php?q=$PATH should return $PATH'
        );

        self::assertEquals(
            "tasty/cake%20pie",
            _get_query("/test/index.php?q=tasty/cake%20pie"),
            'URL encoded paths should be left alone'
        );
        self::assertEquals(
            "tasty/cake%20pie",
            _get_query("/test/tasty/cake%20pie"),
            'URL encoded queries should be left alone'
        );

        self::assertEquals(
            "",
            _get_query("/test/"),
            'If just viewing install directory, should return /'
        );
        self::assertEquals(
            "",
            _get_query("/test/index.php"),
            'If just viewing index.php, should return /'
        );

        self::assertEquals(
            "post/list/tasty%2Fcake/1",
            _get_query("/test/post/list/tasty%2Fcake/1"),
            'URL encoded niceurls should be left alone, even encoded slashes'
        );
        self::assertEquals(
            "post/list/tasty%2Fcake/1",
            _get_query("/test/index.php?q=post/list/tasty%2Fcake/1"),
            'URL encoded uglyurls should be left alone, even encoded slashes'
        );
    }

    public function tearDown(): void
    {
        global $config;
        $config->set_bool(SetupConfig::NICE_URLS, true);
        parent::tearDown();
    }
}
