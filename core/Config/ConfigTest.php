<?php

declare(strict_types=1);

namespace Shimmie2;

final class ConfigTest extends ShimmiePHPUnitTestCase
{
    public function testGetInt(): void
    {
        self::assertEquals(
            (new TestConfig([], ["foo" => "42"]))->get_int("foo"),
            42,
            "get_int should return the value of a setting when it is set correctly"
        );

        self::assertEquals(
            (new TestConfig(["foo" => "42"], []))->get_int("foo"),
            42,
            "get_int should return default when a value is not set"
        );

        self::assertEquals(
            (new TestConfig(["foo" => "42"], ["foo" => "123"]))->get_int("foo"),
            123,
            "get_int should return value when value and default are set"
        );

        self::assertNull(
            (new TestConfig(["foo" => "42"], ["foo" => "waffo"]))->get_int("foo"),
            "get_int should return default when a setting is set incorrectly"
        );

        self::assertNull(
            (new TestConfig([], []))->get_int("foo"),
            "get_int should return null when a setting is not set"
        );

        self::assertException(ConfigException::class, function () {
            (new TestConfig([], []))->req_int("foo");
        }, "req_int should throw an exception when a setting is not set");
    }
}
