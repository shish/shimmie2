<?php

declare(strict_types=1);

namespace Shimmie2;

final class ConfigTest extends ShimmiePHPUnitTestCase
{
    public function testGetInt(): void
    {
        self::assertEquals(
            (new TestConfig(["foo" => "42"]))->get_int("foo"),
            42,
            "get_int should return the value of a setting when it is set correctly"
        );

        self::assertEquals(
            (new TestConfig(["foo" => "waffo"]))->get_int("foo", 42),
            42,
            "get_int should return the default value when a setting is set incorrectly"
        );

        self::assertEquals(
            (new TestConfig([]))->get_int("foo", 42),
            42,
            "get_int should return the default value when a setting is not set"
        );

        self::assertNull(
            (new TestConfig([]))->get_int("foo"),
            "get_int should return null when a setting is not set and no default is specified"
        );

        self::assertException(ConfigException::class, function () {
            (new TestConfig([]))->req_int("foo");
        }, "req_int should throw an exception when a setting is not set");
    }
}
