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
    public function set_float(string $name, float $value): void
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
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_int(string $name, int $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = (string)$value;
        }
    }

    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_float(string $name, float $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = (string)$value;
        }
    }

    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_string(string $name, string $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = $value;
        }
    }

    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_bool(string $name, bool $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = $value ? 'Y' : 'N';
        }
    }

    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     *
     * @param mixed[] $value
     */
    public function set_default_array(string $name, array $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = implode(",", $value);
        }
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
        $val = $this->get($name, $default);
        if (is_null($val) || !is_numeric($val)) {
            return $default;
        }
        return (int)$val;
    }

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     *
     * @template T of float|null
     * @param T $default
     * @return T|float
     */
    public function get_float(string $name, ?float $default = null): ?float
    {
        $val = $this->get($name, $default);
        if (is_null($val) || !is_numeric($val)) {
            return $default;
        }
        return (float)$val;
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
        return $this->get($name, $default);
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
        $val = $this->get($name, $default);
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

    private function get(string $name, mixed $default = null): mixed
    {
        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else {
            return $default;
        }
    }
}


/**
 * Loads the config list from a table in a given database, the table should
 * be called config and have the schema:
 *
 * \code
 *  CREATE TABLE config(
 *      name VARCHAR(255) NOT NULL,
 *      value TEXT
 *  );
 * \endcode
 */
class DatabaseConfig extends Config
{
    private string $cache_name;

    /**
     * @param array<string,string> $defaults
     */
    public function __construct(
        private Database $database,
        private string $table_name = "config",
        private ?string $sub_column = null,
        private ?string $sub_value = null,
        array $defaults = [],
    ) {
        global $cache;

        $this->cache_name = empty($sub_value) ? "config" : "config_{$sub_column}_{$sub_value}";
        $this->values = array_merge(
            $defaults,
            cache_get_or_set($this->cache_name, fn () => $this->get_values()),
        );
    }

    private function get_values(): mixed
    {
        $values = [];

        $query = "SELECT name, value FROM {$this->table_name}";
        $args = [];

        if (!empty($this->sub_column) && !empty($this->sub_value)) {
            $query .= " WHERE {$this->sub_column} = :sub_value";
            $args["sub_value"] = $this->sub_value;
        }

        foreach ($this->database->get_all($query, $args) as $row) {
            // versions prior to 2.12 would store null
            // instead of deleting the row
            if (!is_null($row["value"])) {
                $values[$row["name"]] = $row["value"];
            }
        }

        return $values;
    }

    protected function save(string $name): void
    {
        global $cache;

        $query = "DELETE FROM {$this->table_name} WHERE name = :name";
        $args = ["name" => $name];
        $cols = ["name","value"];
        $params = [":name",":value"];
        if (!empty($this->sub_column) && !empty($this->sub_value)) {
            $query .= " AND $this->sub_column = :sub_value";
            $args["sub_value"] = $this->sub_value;
            $cols[] = $this->sub_column;
            $params[] = ":sub_value";
        }

        $this->database->execute($query, $args);

        if (isset($this->values[$name])) {
            $args["value"] = $this->values[$name];
            $this->database->execute(
                "INSERT INTO {$this->table_name} (".join(",", $cols).") VALUES (".join(",", $params).")",
                $args
            );
        }

        // rather than deleting and having some other request(s) do a thundering
        // herd of race-conditioned updates, just save the updated version once here
        $cache->set($this->cache_name, $this->values);
        $this->database->notify($this->cache_name);
    }
}

class TestConfig extends Config
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function save(string $name): void
    {
    }
}

abstract class BaseConfigGroup
{
    public const KEY = "";
    public ?string $title = null;
    public ?int $position = null;

    public static function get_group_for_entry_by_name(string $name): ?BaseConfigGroup
    {
        foreach (get_subclasses_of(BaseConfigGroup::class) as $class) {
            $config = new $class();
            assert(is_a($config, BaseConfigGroup::class));
            foreach ((new \ReflectionClass($class))->getConstants() as $const => $value) {
                if ($value === $name) {
                    return $config;
                }
            }
        }
        return null;
    }

    /**
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $fields = [];
        $refl_config = new \ReflectionClass($this);
        foreach ($refl_config->getConstants() as $const => $key) {
            $refl_const = $refl_config->getReflectionConstant($const);
            if (!$refl_const) {
                continue;
            }
            $attributes = $refl_const->getAttributes(ConfigMeta::class);
            if (count($attributes) == 0) {
                continue;
            }
            /** @var ConfigMeta $meta */
            $meta = $attributes[0]->newInstance();
            $fields[$key] = $meta;
        }
        return $fields;
    }

    public function tweak_html(\MicroHTML\HTMLElement $html): \MicroHTML\HTMLElement
    {
        return $html;
    }

    /**
     * @return array<string, string>
     */
    public static function get_all_defaults(): array
    {
        return cache_get_or_set(
            get_called_class() . "_defaults_" . VERSION,
            fn () => self::_get_all_defaults(),
            60
        );
    }

    /**
     * @return array<string, string>
     */
    public static function _get_all_defaults(): array
    {
        $defaults = [];
        $base = get_called_class();
        foreach (get_subclasses_of($base) as $class) {
            $refl_config = new \ReflectionClass($class);
            $config = new $class();
            assert(is_a($config, $base));
            if (!Extension::is_enabled($config::KEY)) {
                continue;
            }
            foreach ($refl_config->getConstants() as $const => $value) {
                $refl_const = $refl_config->getReflectionConstant($const);
                if (!$refl_const) {
                    continue;
                }
                $attributes = $refl_const->getAttributes(ConfigMeta::class);
                if (count($attributes) == 0) {
                    continue;
                }
                /** @var ConfigMeta $meta */
                $meta = $attributes[0]->newInstance();
                if ($meta->default !== null) {
                    $defaults[$value] = match ($meta->type) {
                        ConfigType::BOOL => $meta->default ? "true" : "false",
                        ConfigType::INT => (string)($meta->default),
                        ConfigType::STRING => (string)($meta->default),
                        ConfigType::ARRAY => implode(",", $meta->default),
                    };
                }
            }
        }
        return $defaults;
    }
}

abstract class ConfigGroup extends BaseConfigGroup
{
}
abstract class UserConfigGroup extends BaseConfigGroup
{
}

enum ConfigType
{
    case BOOL;
    case INT;
    case STRING;
    case ARRAY;
}

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
readonly class ConfigMeta
{
    /** @var "bool"|"int"|"shorthand_int"|"text"|"longtext"|"multichoice"|"color" */
    public string $ui_type;

    /**
     * @param "shorthand_int"|"longtext"|"color" $ui_type Override the default UI renderer
     * @param array<string, string>|callable-string|null $options A list of key-value pairs, or the name of a function to call to generate pairs
     */
    public function __construct(
        public string $label,
        public ConfigType $type,
        ?string $ui_type = null,
        public mixed $default = null,
        public array|string|null $options = null,
        public ?string $permission = null,
        public ?string $help = null,
        public bool $advanced = false,
    ) {
        $this->ui_type = $ui_type ?? match($type) {
            ConfigType::BOOL => "bool",
            ConfigType::INT => "int",
            ConfigType::STRING => "text",
            ConfigType::ARRAY => "multichoice",
        };
    }
}
