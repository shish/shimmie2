<?php

declare(strict_types=1);

namespace Shimmie2;

final class DatabaseTest extends ShimmiePHPUnitTestCase
{
    public function testCountDatabase(): void
    {
        global $database;
        self::assertGreaterThan(0, $database->count_tables());
    }
}
