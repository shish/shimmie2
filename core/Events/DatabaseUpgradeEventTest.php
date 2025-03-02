<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class DatabaseUpgradeEventTest extends TestCase
{
    public function testDatabaseUpgrade(): void
    {
        $e = send_event(new DatabaseUpgradeEvent());
        $this->assertFalse($e->stop_processing);
    }
}
