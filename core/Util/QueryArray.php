<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Same idea as URLSearchParams in JS
 *
 * @implements \ArrayAccess<string, string|string[]>
 */
final class QueryArray implements \ArrayAccess
{
    /**
     * @param array<string, string|string[]> $params
     */
    public function __construct(private array $params = [])
    {
    }

    /**
     * @return array<string, string|string[]>
     */
    public function toArray(): array
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @param string|string[] $value
     */
    public function set(string $key, string|array $value): void
    {
        $this->params[$key] = $value;
    }

    public function get(string $key): ?string
    {
        $val = $this->params[$key] ?? null;
        if (is_array($val)) {
            $val = $val[0];
        }
        return $val;
    }

    public function req(string $key): string
    {
        return $this->get($key) ?? throw new \InvalidArgumentException("Required parameter '$key' not found");
    }

    /**
     * @return string[]
     */
    public function getAll(string $key): array
    {
        $val = $this->params[$key] ?? [];
        if (is_string($val)) {
            $val = [$val];
        }
        return $val;
    }

    // ArrayAccess methods
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->params[$offset]);
    }

    public function offsetGet(mixed $offset): ?string
    {
        $val = $this->params[$offset] ?? null;
        if (is_array($val)) {
            $val = $val[0];
        }
        return $val;
    }

    /**
     * @param string $offset
     * @param string|string[] $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->params[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->params[$offset]);
    }
}
