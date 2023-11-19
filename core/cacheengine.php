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

    public function getMultiple($keys, $default = null)
    {
        $this->tracer->begin("Cache Get Multiple", ["keys" => $keys]);
        $val = $this->engine->getMultiple($keys, $default);
        $this->tracer->end();
        return $val;
    }

    public function setMultiple($values, $ttl = null)
    {
        $this->tracer->begin("Cache Set Multiple", ["keys" => array_keys($values)]);
        $val = $this->engine->setMultiple($values, $ttl);
        $this->tracer->end();
        return $val;
    }

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
    $matches = [];
    $c = null;
    if ($dsn && preg_match("#(.*)://(.*)#", $dsn, $matches) && !isset($_GET['DISABLE_CACHE'])) {
        if ($matches[1] == "memcached" || $matches[1] == "memcache") {
            $hp = explode(":", $matches[2]);
            $memcache = new \Memcached();
            $memcache->addServer($hp[0], (int)$hp[1]);
            $c = new \Sabre\Cache\Memcached($memcache);
        } elseif ($matches[1] == "apc") {
            $c = new \Sabre\Cache\Apcu();
        } elseif ($matches[1] == "redis") {
            $hp = explode(":", $matches[2]);
            $redis = new \Predis\Client([
                'scheme' => 'tcp',
                'host' => $hp[0],
                'port' => (int)$hp[1]
            ], ['prefix' => 'shm:']);
            $c = new \Naroga\RedisCache\Redis($redis);
        }
    }
    if(is_null($c)) {
        $c = new \Sabre\Cache\Memory();
    }
    global $_tracer;
    return new EventTracingCache($c, $_tracer);
}
