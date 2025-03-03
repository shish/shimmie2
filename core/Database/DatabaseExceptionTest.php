<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class DatabaseExceptionTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = new DatabaseException('Bad Query', 'SCELEECT * FROM foo', []);
        $this->assertInstanceOf(DatabaseException::class, $exception);
    }
}
