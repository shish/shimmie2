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
        $this->tracer->startSpan("Cache Get", ["key" => $key]);
        $val = $this->engine->get($key, $sentinel);
        if ($val !== $sentinel) {
            $res = "hit";
            $this->hits++;
        } else {
            $res = "miss";
            $val = $default;
            $this->misses++;
        }
        $this->tracer->endSpan(attributes: ["result" => $res]);
        return $val;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->tracer->startSpan("Cache Set", ["key" => $key, "ttl" => $ttl]);
        $val = $this->engine->set($key, $value, $ttl);
        $this->tracer->endSpan();
        return $val;
    }

    public function delete($key)
    {
        $this->tracer->startSpan("Cache Delete", ["key" => $key]);
        $val = $this->engine->delete($key);
        $this->tracer->endSpan();
        return $val;
    }

    public function clear()
    {
        $this->tracer->startSpan("Cache Clear");
        $val = $this->engine->clear();
        $this->tracer->endSpan();
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
        $this->tracer->startSpan("Cache Get Multiple", ["keys" => $keys]);
        $val = $this->engine->getMultiple($keys, $default);
        $this->tracer->endSpan();
        return $val;
    }

    /**
     * @param array<string, mixed> $values
     */
    // @phpstan-ignore-next-line
    public function setMultiple($values, $ttl = null)
    {
        $this->tracer->startSpan("Cache Set Multiple", ["keys" => array_keys($values)]);
        $val = $this->engine->setMultiple($values, $ttl);
        $this->tracer->endSpan();
        return $val;
    }

    /**
     * @param string[] $keys
     */
    // @phpstan-ignore-next-line
    public function deleteMultiple($keys)
    {
        $this->tracer->startSpan("Cache Delete Multiple", ["keys" => $keys]);
        $val = $this->engine->deleteMultiple($keys);
        $this->tracer->endSpan();
        return $val;
    }

    public function has($key)
    {
        $this->tracer->startSpan("Cache Has", ["key" => $key]);
        $val = $this->engine->has($key);
        $this->tracer->endSpan(attributes: ["exists" => $val]);
        return $val;
    }
}
