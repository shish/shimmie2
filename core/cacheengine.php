<?php
interface CacheEngine {
	public function get(string $key);
	public function set(string $key, $val, int $time=0);
	public function delete(string $key);
	public function get_hits(): int;
	public function get_misses(): int;
}

class NoCache implements CacheEngine {
	public function get(string $key) {return false;}
	public function set(string $key, $val, int $time=0) {}
	public function delete(string $key) {}
	public function get_hits(): int {return 0;}
	public function get_misses(): int {return 0;}
}

class MemcacheCache implements CacheEngine {
	/** @var \Memcache|null */
	public $memcache=null;
	/** @var int */
	private $hits=0;
	/** @var int */
	private $misses=0;

	public function __construct(string $args) {
		$hp = explode(":", $args);
		$this->memcache = new Memcache;
		@$this->memcache->pconnect($hp[0], $hp[1]);
	}

	public function get(string $key) {
		$val = $this->memcache->get($key);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			$hit = $val === false ? "miss" : "hit";
			file_put_contents("data/cache.log", "Cache $hit: $key\n", FILE_APPEND);
		}
		if($val !== false) {
			$this->hits++;
			return $val;
		}
		else {
			$this->misses++;
			return false;
		}
	}

	public function set(string $key, $val, int $time=0) {
		$this->memcache->set($key, $val, false, $time);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache set: $key ($time)\n", FILE_APPEND);
		}
	}

	public function delete(string $key) {
		$this->memcache->delete($key);
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache delete: $key\n", FILE_APPEND);
		}
	}

	public function get_hits(): int {return $this->hits;}
	public function get_misses(): int {return $this->misses;}
}

class MemcachedCache implements CacheEngine {
	/** @var \Memcached|null */
	public $memcache=null;
	/** @var int */
	private $hits=0;
	/** @var int */
	private $misses=0;

	public function __construct(string $args) {
		$hp = explode(":", $args);
		$this->memcache = new Memcached;
		#$this->memcache->setOption(Memcached::OPT_COMPRESSION, False);
		#$this->memcache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_PHP);
		#$this->memcache->setOption(Memcached::OPT_PREFIX_KEY, phpversion());
		$this->memcache->addServer($hp[0], $hp[1]);
	}

	public function get(string $key) {
		$key = urlencode($key);

		$val = $this->memcache->get($key);
		$res = $this->memcache->getResultCode();

		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			$hit = $res == Memcached::RES_SUCCESS ? "hit" : "miss";
			file_put_contents("data/cache.log", "Cache $hit: $key\n", FILE_APPEND);
		}
		if($res == Memcached::RES_SUCCESS) {
			$this->hits++;
			return $val;
		}
		else if($res == Memcached::RES_NOTFOUND) {
			$this->misses++;
			return false;
		}
		else {
			error_log("Memcached error during get($key): $res");
			return false;
		}
	}

	public function set(string $key, $val, int $time=0) {
		$key = urlencode($key);

		$this->memcache->set($key, $val, $time);
		$res = $this->memcache->getResultCode();
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache set: $key ($time)\n", FILE_APPEND);
		}
		if($res != Memcached::RES_SUCCESS) {
			error_log("Memcached error during set($key): $res");
		}
	}

	public function delete(string $key) {
		$key = urlencode($key);

		$this->memcache->delete($key);
		$res = $this->memcache->getResultCode();
		if((DEBUG_CACHE === true) || (is_null(DEBUG_CACHE) && @$_GET['DEBUG_CACHE'])) {
			file_put_contents("data/cache.log", "Cache delete: $key\n", FILE_APPEND);
		}
		if($res != Memcached::RES_SUCCESS && $res != Memcached::RES_NOTFOUND) {
			error_log("Memcached error during delete($key): $res");
		}
	}

	public function get_hits(): int {return $this->hits;}
	public function get_misses(): int {return $this->misses;}
}

class APCCache implements CacheEngine {
	public $hits=0, $misses=0;

	public function __construct(string $args) {
		// $args is not used, but is passed in when APC cache is created.
	}

	public function get(string $key) {
		$val = apc_fetch($key);
		if($val) {
			$this->hits++;
			return $val;
		}
		else {
			$this->misses++;
			return false;
		}
	}

	public function set(string $key, $val, int $time=0) {
		apc_store($key, $val, $time);
	}

	public function delete(string $key) {
		apc_delete($key);
	}

	public function get_hits(): int {return $this->hits;}
	public function get_misses(): int {return $this->misses;}
}

class RedisCache implements CacheEngine {
	public $hits=0, $misses=0;
	private $redis=null;

	public function __construct(string $args) {
		$this->redis = new Redis();
		$hp = explode(":", $args);
		$this->redis->pconnect($hp[0], $hp[1]);
		$this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
		$this->redis->setOption(Redis::OPT_PREFIX, 'shm:');
	}

	public function get(string $key) {
		$val = $this->redis->get($key);
		if($val !== false) {
			$this->hits++;
			return $val;
		}
		else {
			$this->misses++;
			return false;
		}
	}

	public function set(string $key, $val, int $time=0) {
		if($time > 0) {
			$this->redis->setEx($key, $time, $val);
		}
		else {
			$this->redis->set($key, $val);
		}
	}

	public function delete(string $key) {
		$this->redis->delete($key);
	}

	public function get_hits(): int {return $this->hits;}
	public function get_misses(): int {return $this->misses;}
}
