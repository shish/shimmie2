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
    public array $values = [];

    /**
     * Save the list of name:value pairs to wherever they came from,
     * so that the next time a page is loaded it will use the new
     * configuration.
     */
    abstract protected function save(string $name): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_int(string $name, int $value): void
    {
        $this->values[$name] = (string)$value;
        $this->save($name);
    }

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_string(string $name, string $value): void
    {
        $this->values[$name] = $value;
        $this->save($name);
    }

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_bool(string $name, bool $value): void
    {
        $this->values[$name] = $value ? 'Y' : 'N';
        $this->save($name);
    }

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     *
     * @param mixed[] $value
     */
    public function set_array(string $name, array $value): void
    {
        $this->values[$name] = implode(",", $value);
        $this->save($name);
    }

    /**
     * Delete a configuration option.
     */
    public function delete(string $name): void
    {
        unset($this->values[$name]);
        $this->save($name);
    }

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     *
     * @template T of int|null
     * @param T $default
     * @return T|int
     */
    public function get_int(string $name, ?int $default = null): ?int
    {
        $val = $this->get($name);
        if (is_null($val) || !is_numeric($val)) {
            return $default;
        }
        return (int)$val;
    }

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     *
     * @template T of string|null
     * @param T $default
     * @return T|string
     */
    public function get_string(string $name, ?string $default = null): ?string
    {
        $val = $this->get($name);
        if (is_null($val)) {
            return $default;
        }
        return $val;
    }

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     *
     * @template T of bool|null
     * @param T $default
     * @return T|bool
     */
    public function get_bool(string $name, ?bool $default = null): ?bool
    {
        $val = $this->get($name);
        if (is_null($val)) {
            return $default;
        }
        return bool_escape($val);
    }

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     *
     * @template T of array<string>|null
     * @param T $default
     * @return T|array<string>
     */
    public function get_array(string $name, ?array $default = null): ?array
    {
        $val = $this->get($name);
        if (is_null($val)) {
            return $default;
        }
        if (empty($val)) {
            return [];
        }
        return explode(",", $val);
    }

    private function get(string $name): ?string
    {
        return $this->values[$name] ?? null;
    }

    public function req_int(string $name, ?int $default = null): int
    {
        $val = $this->get_int($name);
        if (is_null($val)) {
            throw new ConfigException("Missing required integer value for '$name'");
        }
        return $val;
    }

    public function req_string(string $name, ?string $default = null): string
    {
        $val = $this->get_string($name);
        if (is_null($val)) {
            throw new ConfigException("Missing required string value for '$name'");
        }
        return $val;
    }

    public function req_bool(string $name, ?bool $default = null): bool
    {
        $val = $this->get_bool($name);
        if (is_null($val)) {
            throw new ConfigException("Missing required boolean value for '$name'");
        }
        return $val;
    }

    /**
     * @param array<string>|null $default
     * @return array<string>
     */
    public function req_array(string $name, ?array $default = null): array
    {
        $val = $this->get_array($name);
        if (is_null($val)) {
            throw new ConfigException("Missing required array value for '$name'");
        }
        return $val;
    }
}
