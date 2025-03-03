<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class InstallerExceptionTest extends TestCase
{
    public function testInstallerExceptionCanBeCreated(): void
    {
        $exception = new InstallerException('Test title', 'Test body', 1);
        $this->assertInstanceOf(InstallerException::class, $exception);
    }
}
