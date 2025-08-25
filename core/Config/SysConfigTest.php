<?php

declare(strict_types=1);

namespace Shimmie2;

final class SysConfigTest extends ShimmiePHPUnitTestCase
{
    public function testMethods(): void
    {
        SysConfig::getDatabaseDsn();
        SysConfig::getDatabaseTimeout();
        SysConfig::getCacheDsn();
        SysConfig::getTimezone();
        SysConfig::getTraceFile();
        self::assertEquals(0.0, SysConfig::getTraceThreshold());
    }
}
