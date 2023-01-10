<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class TestInit extends TestCase
{
    public function testInitExt()
    {
        send_event(new InitExtEvent());
        $this->assertTrue(true);
    }

    public function testDatabaseUpgrade()
    {
        send_event(new DatabaseUpgradeEvent());
        $this->assertTrue(true);
    }
}
