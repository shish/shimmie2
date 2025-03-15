<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class InstallerExceptionTest extends TestCase
{
    public function testInstallerExceptionCanBeCreated(): void
    {
        $exception = new InstallerException('Test title', 'Test body', 1);
        self::assertInstanceOf(InstallerException::class, $exception);
    }
}
