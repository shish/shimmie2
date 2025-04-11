<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Common methods for manipulating a map of config values,
 * loading and saving is left to the concrete implementation
 *
 * @phpstan-type ConfigValue bool|int|string|array<string>
 */
abstract class Config
{
    /** @var array<string, ConfigMeta> */
    public array $metas = [];
    /** @var array<string, ConfigValue> */
    public array $values = [];

    /**
     * @param ConfigValue $value
     */
    public function set(string $name, mixed $value): void
    {
        if (
            isset($this->values[$name])
            && isset($this->metas[$name])
            && $this->values[$name] === $this->metas[$name]->default
        ) {
            unset($this->values[$name]);
        }
        $this->values[$name] = $value;
        $this->save($name);
    }

    /**
     * @return ConfigValue|null $value
     */
    public function get(string $name, ?ConfigType $type = null): mixed
    {
        $value = $this->values[$name] ?? $this->metas[$name]->default ?? null;
        if ($type !== null && is_string($value)) {
            $value = $type->fromString($value);
        }
        return $value;
    }

    public function delete(string $name): void
    {
        unset($this->values[$name]);
        $this->save($name);
    }

    /**
     * @param ConfigValue $value
     */
    protected static function val2str(mixed $value): string
    {
        return match(gettype($value)) {
            'boolean' => $value ? 'Y' : 'N',
            'integer' => (string)$value,
            'string' => $value,
            'array' => implode(",", $value),
        };
    }

    /**
     * Save the list of name:value pairs to wherever they came from,
     * so that the next time a page is loaded it will use the new
     * configuration.
     */
    abstract protected function save(string $name): void;
}
