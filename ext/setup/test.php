<?php

declare(strict_types=1);

namespace Shimmie2;

final class SetupTest extends ShimmiePHPUnitTestCase
{
    public function testNiceUrlsTest(): void
    {
        # XXX: this only checks that the text is "ok", to check
        # for a bug where it was coming out as "\nok"; it doesn't
        # check that niceurls actually work
        self::get_page('nicetest');
        self::assert_content("ok");
        self::assert_no_content("\n");
    }

    public function testNiceDebug(): void
    {
        // the automatic testing for shimmie2-examples depends on this
        $page = self::get_page('nicedebug/foo%2Fbar/1');
        self::assertSame(
            [
                "args" => ["nicedebug", "foo%2Fbar", "1"],
                "theme" => "default",
                "nice_urls" => true,
                "base" => "/test",
                'absolute_base' => 'http://cli-command/test',
                "base_link" => "/test/",
                'search_example' => '/test/post/list/AC%2FDC/1',
            ],
            \Safe\json_decode($page->data, true)
        );
    }

    public function testAuthAnon(): void
    {
        self::assertException(PermissionDenied::class, function () {
            self::get_page('setup');
        });
    }

    public function testAuthUser(): void
    {
        self::log_in_as_user();
        self::assertException(PermissionDenied::class, function () {
            self::get_page('setup');
        });
    }

    public function testAuthAdmin(): void
    {
        self::log_in_as_admin();
        self::get_page('setup');
        self::assert_title("Shimmie Setup");
        self::assert_text("General");
    }

    public function testAdvanced(): void
    {
        self::log_in_as_admin();
        self::get_page('setup', ['advanced' => 'on']);
        self::assert_title("Shimmie Setup");
        self::assert_text("Minimum free space");
    }
}
