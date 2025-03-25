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

        self::assertNull(
            (new TestConfig(["foo" => "waffo"]))->get_int("foo"),
            "get_int should return null when a setting is set incorrectly"
        );

        self::assertNull(
            (new TestConfig([]))->get_int("foo"),
            "get_int should return null when a setting is not set"
        );

        self::assertException(ConfigException::class, function () {
            (new TestConfig([]))->req_int("foo");
        }, "req_int should throw an exception when a setting is not set");
    }
}
