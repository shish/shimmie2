<?php

declare(strict_types=1);

namespace Shimmie2;

final class SetupTest extends ShimmiePHPUnitTestCase
{
    public function testParseSettings(): void
    {
        self::assertEquals(
            [
                "mynull" => null,
                "mystring" => "hello world!",
                "myint" => 42 * 1024,
                "mybool_true" => true,
                "mybool_false" => false,
                "myarray" => ["hello", "world"],
                "emptystring" => null,
                "emptyint" => null,
                "emptybool" => null,
                "emptyarray" => null,
            ],
            ConfigSaveEvent::postToSettings(new QueryArray([
                // keys in POST that don't start with _type or _config are ignored
                "some_post" => "value",
                // _type with no _config means the value is null
                "_type_mynull" => "string",
                // strings left as-is
                "_type_mystring" => "string",
                "_config_mystring" => "hello world!",
                // ints parsed from human-readable form
                "_type_myint" => "int",
                "_config_myint" => "42KB",
                // HTML booleans (HTML checkboxes are "on" or undefined, there is no "off")
                "_type_mybool_true" => "bool",
                "_config_mybool_true" => "on",
                "_type_mybool_false" => "bool",
                // Arrays are... passed as arrays? Does this work?
                "_type_myarray" => "array",
                "_config_myarray" => ["hello", "world"],
                // Empty things should be null
                "_type_emptystring" => "string",
                "_config_emptystring" => "",
                "_type_emptyint" => "int",
                "_config_emptyint" => "",
                "_type_emptybool" => "bool",
                "_config_emptybool" => "",
                "_type_emptyarray" => "array",
                "_config_emptyarray" => "",
            ]))
        );

        self::assertException(InvalidInput::class, function () {
            ConfigSaveEvent::postToSettings(new QueryArray([
                "_type_myint" => "cake",
                "_config_myint" => "tasty",
            ]));
        });
    }
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
        self::assertEquals(
            [
                "args" => ["nicedebug","foo%2Fbar","1"],
                "theme" => "default",
                "nice_urls" => true,
                "base" => "/test",
                "base_link" => "/test/",
                'absolute_base' => 'http://cli-command/test',
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
