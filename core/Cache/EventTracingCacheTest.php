<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class EventTracingCacheTest extends TestCase
{
    public function testBits(): void
    {
        $t = new \EventTracer();
        $b = new \Sabre\Cache\Memory();
        $c = new EventTracingCache($b, $t);

        $c->set('key', 'value');
        $this->assertEquals('value', $c->get('key'));
        $this->assertTrue($c->has('key'));
        $c->clear();
        $this->assertFalse($c->has('key'));
    }

    public function testMultipleBits(): void
    {
        $t = new \EventTracer();
        $b = new \Sabre\Cache\Memory();
        $c = new EventTracingCache($b, $t);

        $c->setMultiple(['key1' => 'value1', 'key2' => 'value2']);
        foreach ($c->getMultiple(['key1', 'key2']) as $key => $value) {
            $this->assertEquals($value, $c->get($key));
        }
        $c->deleteMultiple(['key1', 'key2']);
        $this->assertFalse($c->has('key1'));
        $this->assertFalse($c->has('key2'));
    }
}
