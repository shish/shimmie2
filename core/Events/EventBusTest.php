<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

final class EventBusTest extends TestCase
{
    private Path $cache;

    public function setUp(): void
    {
        parent::setUp();
        $this->cache = new Path("cache/event_listeners");
        Ctx::$config->set(SetupConfig::CACHE_EVENT_LISTENERS, false);
        if ($this->cache->exists()) {
            Filesystem::deltree($this->cache);
        }
    }

    public function testInit(): void
    {
        $b = new EventBus();
        self::assertEquals(0, $b->event_count);
    }

    public function testCache(): void
    {
        Ctx::$config->set(SetupConfig::CACHE_EVENT_LISTENERS, true);

        self::assertFalse($this->cache->exists());

        // Create cache file
        $t1 = ftime();
        new EventBus();
        $t2 = ftime();

        self::assertTrue($this->cache->exists());

        // Load from cache
        $t3 = ftime();
        new EventBus();
        $t4 = ftime();

        self::assertTrue($this->cache->exists());

        // Loading from cache should be faster than creating it
        self::assertLessThan($t2 - $t1, $t4 - $t3);
    }

    public function tearDown(): void
    {
        Ctx::$config->set(SetupConfig::CACHE_EVENT_LISTENERS, false);
        if ($this->cache->exists()) {
            Filesystem::deltree($this->cache);
        }
        parent::tearDown();
    }
}
