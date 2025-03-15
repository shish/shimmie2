<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class EventTracingCacheTest extends TestCase
{
    public function testBits(): void
    {
        $t = new \EventTracer();
        $b = new \Sabre\Cache\Memory();
        $c = new EventTracingCache($b, $t);

        $c->set('key', 'value');
        self::assertEquals('value', $c->get('key'));
        self::assertTrue($c->has('key'));
        $c->clear();
        self::assertFalse($c->has('key'));
    }

    public function testMultipleBits(): void
    {
        $t = new \EventTracer();
        $b = new \Sabre\Cache\Memory();
        $c = new EventTracingCache($b, $t);

        $c->setMultiple(['key1' => 'value1', 'key2' => 'value2']);
        foreach ($c->getMultiple(['key1', 'key2']) as $key => $value) {
            self::assertEquals($value, $c->get($key));
        }
        $c->deleteMultiple(['key1', 'key2']);
        self::assertFalse($c->has('key1'));
        self::assertFalse($c->has('key2'));
    }
}
