<?php

declare(strict_types=1);

namespace Shimmie2;

use Psr\SimpleCache\CacheInterface;

class EventTracingCache implements CacheInterface
{
    private CacheInterface $engine;
    private \EventTracer $tracer;
    private int $hits = 0;
    private int $misses = 0;

    public function __construct(CacheInterface $engine, \EventTracer $tracer)
    {
        $this->engine = $engine;
        $this->tracer = $tracer;
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
        $this->tracer->begin("Cache Get", ["key" => $key]);
        $val = $this->engine->get($key, $sentinel);
        if ($val != $sentinel) {
            $res = "hit";
            $this->hits++;
        } else {
            $res = "miss";
            $val = $default;
            $this->misses++;
        }
        $this->tracer->end(null, ["result" => $res]);
        return $val;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->tracer->begin("Cache Set", ["key" => $key, "ttl" => $ttl]);
        $val = $this->engine->set($key, $value, $ttl);
        $this->tracer->end();
        return $val;
    }

    public function delete($key)
    {
        $this->tracer->begin("Cache Delete", ["key" => $key]);
        $val = $this->engine->delete($key);
        $this->tracer->end();
        return $val;
    }

    public function clear()
    {
        $this->tracer->begin("Cache Clear");
        $val = $this->engine->clear();
        $this->tracer->end();
        return $val;
    }

    /**
     * @param string[] $keys
     * @param mixed $default
     * @return iterable<mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $this->tracer->begin("Cache Get Multiple", ["keys" => $keys]);
        $val = $this->engine->getMultiple($keys, $default);
        $this->tracer->end();
        return $val;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setMultiple($values, $ttl = null)
    {
        $this->tracer->begin("Cache Set Multiple", ["keys" => array_keys($values)]);
        $val = $this->engine->setMultiple($values, $ttl);
        $this->tracer->end();
        return $val;
    }

    /**
     * @param string[] $keys
     */
    public function deleteMultiple($keys)
    {
        $this->tracer->begin("Cache Delete Multiple", ["keys" => $keys]);
        $val = $this->engine->deleteMultiple($keys);
        $this->tracer->end();
        return $val;
    }

    public function has($key)
    {
        $this->tracer->begin("Cache Has", ["key" => $key]);
        $val = $this->engine->has($key);
        $this->tracer->end(null, ["exists" => $val]);
        return $val;
    }
}

function loadCache(?string $dsn): CacheInterface
{
    $c = null;
    if ($dsn && !isset($_GET['DISABLE_CACHE'])) {
        $url = parse_url($dsn);
        if($url) {
            if ($url['scheme'] == "memcached" || $url['scheme'] == "memcache") {
                $memcache = new \Memcached();
                $memcache->addServer($url['host'], $url['port']);
                $c = new \Sabre\Cache\Memcached($memcache);
            } elseif ($url['scheme'] == "apc") {
                $c = new \Sabre\Cache\Apcu();
            } elseif ($url['scheme'] == "redis") {
                $redis = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => $url['host'] ?? "127.0.0.1",
                    'port' => $url['port'] ?? 6379,
                    'username' => $url['user'] ?? null,
                    'password' => $url['pass'] ?? null,
                ], ['prefix' => 'shm:']);
                $c = new \Naroga\RedisCache\Redis($redis);
            }
        }
    }
    if(is_null($c)) {
        $c = new \Sabre\Cache\Memory();
    }
    global $_tracer;
    return new EventTracingCache($c, $_tracer);
}
