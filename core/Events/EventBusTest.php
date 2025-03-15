<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class EventBusTest extends TestCase
{
    public function testInit(): void
    {
        $b = new EventBus();
        self::assertEquals(0, $b->event_count);
    }
}
