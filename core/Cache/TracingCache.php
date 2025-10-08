<?php

declare(strict_types=1);

namespace Shimmie2;

use Psr\SimpleCache\CacheInterface;

class TracingCache implements CacheInterface
{
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(
        private CacheInterface $engine,
        private \MicroOTLP\Client $tracer
    ) {
    }

    public function get($key, $default = null)
    {
        if ($key === "__etc_cache_hits") {
            return $this->hits;
        }
        if ($key === "__etc_cache_misses") {
            return $this->misses;
        }

        $sentinel = "__etc_sentinel";
        $span = $this->tracer->startSpan("Cache Get", ["key" => $key]);
        $val = $this->engine->get($key, $sentinel);
        if ($val !== $sentinel) {
            $res = "hit";
            $this->hits++;
        } else {
            $res = "miss";
            $val = $default;
            $this->misses++;
        }
        $span->end(attributes: ["result" => $res]);
        return $val;
    }

    public function set($key, $value, $ttl = null)
    {
        $span = $this->tracer->startSpan("Cache Set", ["key" => $key, "ttl" => $ttl]);
        $val = $this->engine->set($key, $value, $ttl);
        $span->end();
        return $val;
    }

    public function delete($key)
    {
        $span = $this->tracer->startSpan("Cache Delete", ["key" => $key]);
        $val = $this->engine->delete($key);
        $span->end();
        return $val;
    }

    public function clear()
    {
        $span = $this->tracer->startSpan("Cache Clear");
        $val = $this->engine->clear();
        $span->end();
        return $val;
    }

    /**
     * @param string[] $keys
     * @param mixed $default
     * @return iterable<mixed>
     */
    // @phpstan-ignore-next-line
    public function getMultiple($keys, $default = null)
    {
        $span = $this->tracer->startSpan("Cache Get Multiple", ["keys" => $keys]);
        $val = $this->engine->getMultiple($keys, $default);
        $span->end();
        return $val;
    }

    /**
     * @param array<string, mixed> $values
     */
    // @phpstan-ignore-next-line
    public function setMultiple($values, $ttl = null)
    {
        $span = $this->tracer->startSpan("Cache Set Multiple", ["keys" => array_keys($values)]);
        $val = $this->engine->setMultiple($values, $ttl);
        $span->end();
        return $val;
    }

    /**
     * @param string[] $keys
     */
    // @phpstan-ignore-next-line
    public function deleteMultiple($keys)
    {
        $span = $this->tracer->startSpan("Cache Delete Multiple", ["keys" => $keys]);
        $val = $this->engine->deleteMultiple($keys);
        $span->end();
        return $val;
    }

    public function has($key)
    {
        $span = $this->tracer->startSpan("Cache Has", ["key" => $key]);
        $val = $this->engine->has($key);
        $span->end(attributes: ["exists" => $val]);
        return $val;
    }
}
