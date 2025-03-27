<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Common methods for manipulating a map of config values,
 * loading and saving is left to the concrete implementation
 */
abstract class Config
{
    /** @var array<string, string> */
    public array $defaults = [];
    /** @var array<string, string> */
    public array $values = [];

    // ================================================================
    // Untyped API
    // ================================================================

    private function set(string $name, string $value): void
    {
        if (
            isset($this->values[$name])
            && isset($this->defaults[$name])
            && $this->values[$name] === $this->defaults[$name]
        ) {
            unset($this->values[$name]);
        }
        $this->values[$name] = $value;
        $this->save($name);
    }

    private function get(string $name): ?string
    {
        return $this->values[$name] ?? $this->defaults[$name] ?? null;
    }

    public function delete(string $name): void
    {
        unset($this->values[$name]);
        $this->save($name);
    }

    /**
     * Save the list of name:value pairs to wherever they came from,
     * so that the next time a page is loaded it will use the new
     * configuration.
     */
    abstract protected function save(string $name): void;

    // ================================================================
    // Typed API
    // ================================================================

    public function set_int(string $name, int $value): void
    {
        $this->set($name, (string)$value);
    }

    public function set_string(string $name, string $value): void
    {
        $this->set($name, $value);
    }

    public function set_bool(string $name, bool $value): void
    {
        $this->set($name, $value ? 'Y' : 'N');
    }

    /** @param string[] $value */
    public function set_array(string $name, array $value): void
    {
        $this->set($name, implode(",", $value));
    }

    public function get_int(string $name): ?int
    {
        $val = $this->get($name);
        return (is_null($val) || !is_numeric($val)) ? null : (int)$val;
    }

    public function get_string(string $name): ?string
    {
        $val = $this->get($name);
        return is_null($val) ? null : $val;
    }

    public function get_bool(string $name): ?bool
    {
        $val = $this->get($name);
        return (is_null($val)) ? null : bool_escape($val);
    }

    /** @return string[] */
    public function get_array(string $name): ?array
    {
        $val = $this->get($name);
        if (is_null($val)) {
            return null;
        }
        if (empty($val)) {
            return [];
        }
        return explode(",", $val);
    }

    public function req_int(string $name): int
    {
        return $this->get_int($name) ?? throw new ConfigException("Missing required integer value for '$name'");
    }

    public function req_string(string $name): string
    {
        return $this->get_string($name) ?? throw new ConfigException("Missing required string value for '$name'");
    }

    public function req_bool(string $name): bool
    {
        return $this->get_bool($name) ?? throw new ConfigException("Missing required boolean value for '$name'");
    }

    /** @return array<string> */
    public function req_array(string $name): array
    {
        return $this->get_array($name) ?? throw new ConfigException("Missing required array value for '$name'");
    }
}
