<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class ConfigTypeTest extends TestCase
{
    public function testFromPost(): void
    {
        self::assertSame(
            "foo",
            ConfigType::STRING->fromPost("foo")
        );
        self::assertSame(
            42,
            ConfigType::INT->fromPost("42")
        );
        self::assertSame(
            0,
            ConfigType::INT->fromPost("0")
        );
        self::assertSame(
            true,
            ConfigType::BOOL->fromPost("on")
        );
        self::assertSame(
            false,
            ConfigType::BOOL->fromPost(null)
        );
        self::assertSame(
            [],
            ConfigType::ARRAY->fromPost([])
        );
    }
}
