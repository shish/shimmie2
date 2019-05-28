<?php
interface CacheEngine
{
    public function get(string $key);
    public function set(string $key, $val, int $time=0);
    public function delete(string $key);
}

class NoCache implements CacheEngine
{
    public function get(string $key)
    {
        return false;
    }
    public function set(string $key, $val, int $time=0)
    {
    }
    public function delete(string $key)
    {
    }
}

class MemcacheCache implements CacheEngine
{
    /** @var ?Memcache */
    public $memcache=null;

    public function __construct(string $args)
    {
        $hp = explode(":", $args);
        $this->memcache = new Memcache;
        @$this->memcache->pconnect($hp[0], $hp[1]);
    }

    public function get(string $key)
    {
        return $this->memcache->get($key);
    }

    public function set(string $key, $val, int $time=0)
    {
        $this->memcache->set($key, $val, false, $time);
    }

    public function delete(string $key)
    {
        $this->memcache->delete($key);
    }
}

class MemcachedCache implements CacheEngine
{
    /** @var ?Memcached */
    public $memcache=null;

    public function __construct(string $args)
    {
        $hp = explode(":", $args);
        $this->memcache = new Memcached;
        #$this->memcache->setOption(Memcached::OPT_COMPRESSION, False);
        #$this->memcache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_PHP);
        #$this->memcache->setOption(Memcached::OPT_PREFIX_KEY, phpversion());
        $this->memcache->addServer($hp[0], $hp[1]);
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

    public function set(string $key, $val, int $time=0)
    {
        $key = urlencode($key);

        $this->memcache->set($key, $val, $time);
        $res = $this->memcache->getResultCode();
        if ($res != Memcached::RES_SUCCESS) {
            error_log("Memcached error during set($key): $res");
        }
    }

    public function delete(string $key)
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

    public function set(string $key, $val, int $time=0)
    {
        apc_store($key, $val, $time);
    }

    public function delete(string $key)
    {
        apc_delete($key);
    }
}

class RedisCache implements CacheEngine
{
    private $redis=null;

    public function __construct(string $args)
    {
        $this->redis = new Redis();
        $hp = explode(":", $args);
        $this->redis->pconnect($hp[0], $hp[1]);
        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $this->redis->setOption(Redis::OPT_PREFIX, 'shm:');
    }

    public function get(string $key)
    {
        return $this->redis->get($key);
    }

    public function set(string $key, $val, int $time=0)
    {
        if ($time > 0) {
            $this->redis->setEx($key, $time, $val);
        } else {
            $this->redis->set($key, $val);
        }
    }

    public function delete(string $key)
    {
        $this->redis->delete($key);
    }
}

class Cache
{
    public $engine;
    public $hits=0;
    public $misses=0;
    public $time=0;

    public function __construct(?string $dsn)
    {
        $matches = [];
        $c = null;
        if ($dsn && preg_match("#(.*)://(.*)#", $dsn, $matches)) {
            if ($matches[1] == "memcache") {
                $c = new MemcacheCache($matches[2]);
            } elseif ($matches[1] == "memcached") {
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
        $val = $this->engine->get($key);
        if ((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
            $hit = $val === false ? "hit" : "miss";
            file_put_contents("data/cache.log", "Cache $hit: $key\n", FILE_APPEND);
        }
        if ($val !== false) {
            $this->hits++;
            return $val;
        } else {
            $this->misses++;
            return false;
        }
    }

    public function set(string $key, $val, int $time=0)
    {
        $this->engine->set($key, $val, $time);
        if ((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
            file_put_contents("data/cache.log", "Cache set: $key ($time)\n", FILE_APPEND);
        }
    }

    public function delete(string $key)
    {
        $this->engine->delete($key);
        if ((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
            file_put_contents("data/cache.log", "Cache delete: $key\n", FILE_APPEND);
        }
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
