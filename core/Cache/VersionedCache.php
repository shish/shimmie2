<?php

declare(strict_types=1);

namespace Shimmie2;

use Psr\SimpleCache\CacheInterface;

class VersionedCache implements CacheInterface
{
    public function __construct(
        private CacheInterface $engine,
        private string $suffix
    ) {
    }

    public function get($key, $default = null)
    {
        return $this->engine->get($key.$this->suffix, $default);
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->engine->set($key.$this->suffix, $value, $ttl);
    }

    public function delete($key)
    {
        return $this->engine->delete($key.$this->suffix);
    }

    public function clear()
    {
        return $this->engine->clear();
    }

    /**
     * @param string[] $keys
     * @param mixed $default
     * @return iterable<mixed>
     */
    public function getMultiple($keys, $default = null)
    {
        $keys = array_map(fn ($key) => $key.$this->suffix, $keys);
        $vals = $this->engine->getMultiple($keys, $default);
        $stripped_vals = [];
        foreach ($vals as $key => $value) {
            $stripped_vals[substr($key, 0, -strlen($this->suffix))] = $value;
        }
        return $stripped_vals;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setMultiple($values, $ttl = null)
    {
        $suffixed = [];
        foreach ($values as $key => $value) {
            $suffixed[$key.$this->suffix] = $value;
        }
        return $this->engine->setMultiple($suffixed, $ttl);
    }

    /**
     * @param string[] $keys
     */
    public function deleteMultiple($keys)
    {
        $keys = array_map(fn ($key) => $key.$this->suffix, $keys);
        return $this->engine->deleteMultiple($keys);
    }

    public function has($key)
    {
        return $this->engine->has($key.$this->suffix);
    }
}
