<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class InitExtEventTest extends TestCase
{
    public function testInitExt(): void
    {
        // InitExt loads default classes, and we don't want to duplicate them
        UserClass::$known_classes = [];
        $e = send_event(new InitExtEvent());
        self::assertFalse($e->stop_processing);
    }
}
