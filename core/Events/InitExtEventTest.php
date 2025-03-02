<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class InitExtEventTest extends TestCase
{
    public function testInitExt(): void
    {
        $e = send_event(new InitExtEvent());
        $this->assertFalse($e->stop_processing);
    }
}
