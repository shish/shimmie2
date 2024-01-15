<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class InitTest extends TestCase
{
    public function testInitExt(): void
    {
        send_event(new InitExtEvent());
        $this->assertTrue(true);
    }

    public function testDatabaseUpgrade(): void
    {
        send_event(new DatabaseUpgradeEvent());
        $this->assertTrue(true);
    }
}
