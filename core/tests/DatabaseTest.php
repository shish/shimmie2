<?php

declare(strict_types=1);

namespace Shimmie2;

class DatabaseTest extends ShimmiePHPUnitTestCase
{
    public function testCountDatabase(): void
    {
        global $database;
        $this->assertGreaterThan(0, $database->count_tables());
    }
}
