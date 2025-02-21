<?php

declare(strict_types=1);

namespace Shimmie2;

require_once "core/imageboard/image.php";

class ConfigTest extends ShimmiePHPUnitTestCase
{
    public function testGetInt(): void
    {
        $this->assertEquals(
            (new TestConfig(["foo" => "42"]))->get_int("foo"),
            42,
            "get_int should return the value of a setting when it is set correctly"
        );

        $config = new TestConfig(["foo" => "waffo"]);
        $this->assertNull(
            (new TestConfig(["foo" => "waffo"]))->get_int("foo"),
            "get_int should return null when a setting is set incorrectly"
        );

        $config = new TestConfig([]);
        $this->assertEquals(
            (new TestConfig([]))->get_int("foo", 42),
            42,
            "get_int should return the default value when a setting is not set"
        );

        $this->assertNull(
            (new TestConfig([]))->get_int("foo"),
            "get_int should return null when a setting is not set and no default is specified"
        );
    }
}
