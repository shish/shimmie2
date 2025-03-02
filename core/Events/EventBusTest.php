<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class EventBusTest extends TestCase
{
    public function testInit(): void
    {
        $b = new EventBus();
        $this->assertEquals(0, $b->event_count);
    }
}
