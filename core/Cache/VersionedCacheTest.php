<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class VersionedCacheTest extends TestCase
{
    public function testBits(): void
    {
        $b = new \Sabre\Cache\Memory();
        $c = new VersionedCache($b, "_versioned");

        $c->set('key', 'value');
        self::assertEquals('value', $c->get('key'));
        self::assertEquals('value', $b->get('key_versioned'));
        self::assertTrue($c->has('key'));
        self::assertTrue($b->has('key_versioned'));
        $c->clear();
        self::assertFalse($c->has('key'));
        self::assertFalse($b->has('key_versioned'));
    }

    public function testMultipleBits(): void
    {
        $b = new \Sabre\Cache\Memory();
        $c = new VersionedCache($b, "_versioned");

        $c->setMultiple(['key1' => 'value1', 'key2' => 'value2']);
        self::assertTrue($b->has("key1_versioned"));
        self::assertTrue($b->has("key2_versioned"));

        self::assertEquals(
            ['key1' => 'value1', 'key2' => 'value2'],
            $c->getMultiple(['key1', 'key2'])
        );
        self::assertEquals(
            ['key1_versioned' => 'value1', 'key2_versioned' => 'value2'],
            $b->getMultiple(['key1_versioned', 'key2_versioned'])
        );

        $c->deleteMultiple(['key1', 'key2']);
        self::assertFalse($c->has('key1'));
        self::assertFalse($c->has('key2'));
    }
}
