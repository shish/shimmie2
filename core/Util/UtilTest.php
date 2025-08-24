<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class UtilTest extends TestCase
{
    /**
     * @return array<array{boolean}>
     */
    public static function niceurl_options(): array
    {
        return [[true], [false]];
    }

    public function test_get_theme(): void
    {
        self::assertEquals("default", get_theme());
    }

    public function test_get_memory_limit(): void
    {
        self::assertGreaterThan(0, get_memory_limit());
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

    public function test_make_link(): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        self::assertEquals(
            "/test/foo",
            (string)make_link("foo")
        );

        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        self::assertEquals(
            "/test/index.php?q=foo",
            (string)make_link("foo")
        );

        Ctx::$config->set(SetupConfig::NICE_URLS, false);
        self::assertEquals(
            "/test/index.php?q=post%2Flist",
            (string)make_link()
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
            'URL encoded niceurl components should be left alone, even encoded slashes'
        );
        self::assertEquals(
            "post/list/tasty%2Fcake/1",
            _get_query("/test/index.php?q=post/list/tasty%2Fcake/1"),
            'URL encoded uglyurl components should be left alone, even encoded slashes'
        );
        self::assertEquals(
            "post/list/tasty%2Fcake/1",
            _get_query("/test/index.php?q=post%2Flist%2Ftasty%252Fcake%2F1"),
            'URL encoded uglyurl components within a URL encoded param should be left alone, even encoded slashes'
        );
    }

    public function test_compare_file_bytes(): void
    {
        $path = shm_tempnam("test_compare_file_bytes");
        try {
            $path->put_contents("abcd");
            // starts with abc
            self::assertTrue(compare_file_bytes($path, [0x61, 0x62, 0x63]));
            // starts with abd
            self::assertFalse(compare_file_bytes($path, [0x61, 0x62, 0x64]));
            // starts with a?c
            self::assertTrue(compare_file_bytes($path, [0x61, null, 0x63]));
            // starts with abcde
            self::assertFalse(compare_file_bytes($path, [0x61, 0x62, 0x63, 0x64, 0x65]));
        } finally {
            $path->unlink();
        }
    }

    public function tearDown(): void
    {
        Ctx::$config->set(SetupConfig::NICE_URLS, true);
        parent::tearDown();
    }
}
