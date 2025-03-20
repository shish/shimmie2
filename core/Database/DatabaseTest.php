<?php

declare(strict_types=1);

namespace Shimmie2;

final class DatabaseTest extends ShimmiePHPUnitTestCase
{
    public function testCountDatabase(): void
    {
        self::assertGreaterThan(0, Ctx::$database->count_tables());
    }
}
