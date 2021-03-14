<?php declare(strict_types=1);
interface CacheEngine
{
    public function get(string $key);
    public function set(string $key, $val, int $time=0): void;
    public function delete(string $key): void;
}

class NoCache implements CacheEngine
{
    public function get(string $key)
    {
        return false;
    }
    public function set(string $key, $val, int $time=0): void
    {
    }
    public function delete(string $key): void
    {
    }
}

class MemcachedCache implements CacheEngine
{
    public ?Memcached $memcache=null;

    public function __construct(string $args)
    {
        $hp = explode(":", $args);
        $this->memcache = new Memcached;
        #$this->memcache->setOption(Memcached::OPT_COMPRESSION, False);
        #$this->memcache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_PHP);
        #$this->memcache->setOption(Memcached::OPT_PREFIX_KEY, phpversion());
        $this->memcache->addServer($hp[0], (int)$hp[1]);
    }

    public function get(string $key)
    {
        $key = urlencode($key);

        $val = $this->memcache->get($key);
        $res = $this->memcache->getResultCode();

        if ($res == Memcached::RES_SUCCESS) {
            return $val;
        } elseif ($res == Memcached::RES_NOTFOUND) {
            return false;
        } else {
            error_log("Memcached error during get($key): $res");
            return false;
        }
    }

    public function set(string $key, $val, int $time=0): void
    {
        $key = urlencode($key);

        $this->memcache->set($key, $val, $time);
        $res = $this->memcache->getResultCode();
        if ($res != Memcached::RES_SUCCESS) {
            error_log("Memcached error during set($key): $res");
        }
    }

    public function delete(string $key): void
    {
        $key = urlencode($key);

        $this->memcache->delete($key);
        $res = $this->memcache->getResultCode();
        if ($res != Memcached::RES_SUCCESS && $res != Memcached::RES_NOTFOUND) {
            error_log("Memcached error during delete($key): $res");
        }
    }
}

class APCCache implements CacheEngine
{
    public function __construct(string $args)
    {
        // $args is not used, but is passed in when APC cache is created.
    }

    public function get(string $key)
    {
        return apc_fetch($key);
    }

    public function set(string $key, $val, int $time=0): void
    {
        apc_store($key, $val, $time);
    }

    public function delete(string $key): void
    {
        apc_delete($key);
    }
}

class RedisCache implements CacheEngine
{
    private Redis $redis;

    public function __construct(string $args)
    {
        $this->redis = new Redis();
        $hp = explode(":", $args);
        $this->redis->pconnect($hp[0], (int)$hp[1]);
        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $this->redis->setOption(Redis::OPT_PREFIX, 'shm:');
    }

    public function get(string $key)
    {
        return $this->redis->get($key);
    }

    public function set(string $key, $val, int $time=0): void
    {
        if ($time > 0) {
            $this->redis->setEx($key, $time, $val);
        } else {
            $this->redis->set($key, $val);
        }
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }
}

class Cache
{
    public $engine;
    public int $hits=0;
    public int $misses=0;
    public int $time=0;

    public function __construct(?string $dsn)
    {
        $matches = [];
        $c = null;
        if ($dsn && preg_match("#(.*)://(.*)#", $dsn, $matches) && !isset($_GET['DISABLE_CACHE'])) {
            if ($matches[1] == "memcached") {
                $c = new MemcachedCache($matches[2]);
            } elseif ($matches[1] == "apc") {
                $c = new APCCache($matches[2]);
            } elseif ($matches[1] == "redis") {
                $c = new RedisCache($matches[2]);
            }
        } else {
            $c = new NoCache();
        }
        $this->engine = $c;
    }

    public function get(string $key)
    {
        global $_tracer;
        $_tracer->begin("Cache Query", ["key"=>$key]);
        $val = $this->engine->get($key);
        if ($val !== false) {
            $res = "hit";
            $this->hits++;
        } else {
            $res = "miss";
            $this->misses++;
        }
        $_tracer->end(null, ["result"=>$res]);
        return $val;
    }

    public function set(string $key, $val, int $time=0)
    {
        global $_tracer;
        $_tracer->begin("Cache Set", ["key"=>$key, "time"=>$time]);
        $this->engine->set($key, $val, $time);
        $_tracer->end();
    }

    public function delete(string $key)
    {
        global $_tracer;
        $_tracer->begin("Cache Delete", ["key"=>$key]);
        $this->engine->delete($key);
        $_tracer->end();
    }

    public function get_hits(): int
    {
        return $this->hits;
    }
    public function get_misses(): int
    {
        return $this->misses;
    }
}
