<?php

declare(strict_types=1);

namespace Shimmie2;

class SetupTest extends ShimmiePHPUnitTestCase
{
    public function testParseSettings(): void
    {
        $this->assertEquals(
            [
                "mynull" => null,
                "mystring" => "hello world!",
                "myint" => 42 * 1024,
                "mybool_true" => true,
                "mybool_false" => false,
                "myarray" => ["hello", "world"],
            ],
            ConfigSaveEvent::postToSettings([
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
            ])
        );

        $this->assertException(InvalidInput::class, function () {
            ConfigSaveEvent::postToSettings([
                "_type_myint" => "cake",
                "_config_myint" => "tasty",
            ]);
        });
    }
    public function testNiceUrlsTest(): void
    {
        # XXX: this only checks that the text is "ok", to check
        # for a bug where it was coming out as "\nok"; it doesn't
        # check that niceurls actually work
        $this->get_page('nicetest');
        $this->assert_content("ok");
        $this->assert_no_content("\n");
    }

    public function testNiceDebug(): void
    {
        // the automatic testing for shimmie2-examples depends on this
        $page = $this->get_page('nicedebug/foo%2Fbar/1');
        $this->assertEquals('{"args":["nicedebug","foo%2Fbar","1"]}', $page->data);
    }

    public function testAuthAnon(): void
    {
        $this->assertException(PermissionDenied::class, function () {
            $this->get_page('setup');
        });
    }

    public function testAuthUser(): void
    {
        $this->log_in_as_user();
        $this->assertException(PermissionDenied::class, function () {
            $this->get_page('setup');
        });
    }

    public function testAuthAdmin(): void
    {
        $this->log_in_as_admin();
        $this->get_page('setup');
        $this->assert_title("Shimmie Setup");
        $this->assert_text("General");
    }

    public function testAdvanced(): void
    {
        $this->log_in_as_admin();
        $this->get_page('setup/advanced');
        $this->assert_title("Shimmie Setup");
        $this->assert_text(ImageConfig::THUMB_QUALITY);
    }
}
