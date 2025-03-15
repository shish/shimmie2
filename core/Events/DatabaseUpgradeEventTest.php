<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class DatabaseUpgradeEventTest extends TestCase
{
    public function testDatabaseUpgrade(): void
    {
        $e = send_event(new DatabaseUpgradeEvent());
        self::assertFalse($e->stop_processing);
    }
}
