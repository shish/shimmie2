<?php

declare(strict_types=1);

namespace Shimmie2;

final class ConfigTest extends ShimmiePHPUnitTestCase
{
    public function testGet(): void
    {
        self::assertEquals(
            (new TestConfig([], ["foo" => 42]))->get("foo"),
            42,
            "should return the value of a setting when it is set correctly"
        );

        self::assertEquals(
            (new TestConfig(["foo" => new ConfigMeta("", type: ConfigType::INT, default: 42)], []))->get("foo"),
            42,
            "should return default when a value is not set"
        );

        self::assertEquals(
            (new TestConfig(["foo" => new ConfigMeta("", type: ConfigType::INT, default: 42)], ["foo" => 123]))->get("foo"),
            123,
            "should return value when value and default are set"
        );

        self::assertNull(
            (new TestConfig([], []))->get("foo"),
            "should return null when a setting is not set"
        );
    }
}
