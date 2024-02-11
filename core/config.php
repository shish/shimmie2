<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Interface Config
 *
 * An abstract interface for altering a name:value pair list.
 */
interface Config
{
    /**
     * Save the list of name:value pairs to wherever they came from,
     * so that the next time a page is loaded it will use the new
     * configuration.
     */
    public function save(string $name = null): void;

    //@{ /*--------------------------------- SET ------------------------------------------------------*/
    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_int(string $name, ?int $value): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_float(string $name, ?float $value): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_string(string $name, ?string $value): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_bool(string $name, ?bool $value): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     *
     * @param mixed[] $value
     */
    public function set_array(string $name, array $value): void;
    //@} /*--------------------------------------------------------------------------------------------*/

    //@{ /*-------------------------------- SET DEFAULT -----------------------------------------------*/
    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_int(string $name, int $value): void;

    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_float(string $name, float $value): void;

    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_string(string $name, string $value): void;

    /**
     * Set a configuration option to a new value, if there is no value currently.
     *
     * Extensions should generally call these from their InitExtEvent handlers.
     * This has the advantage that the values will show up in the "advanced" setup
     * page where they can be modified, while calling get_* with a "default"
     * parameter won't show up.
     */
    public function set_default_bool(string $name, bool $value): void;

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
    public function set_default_array(string $name, array $value): void;
    //@} /*--------------------------------------------------------------------------------------------*/

    //@{ /*--------------------------------- GET ------------------------------------------------------*/
    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_int(string $name, ?int $default = null): ?int;

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_float(string $name, ?float $default = null): ?float;

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_string(string $name, ?string $default = null): ?string;

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_bool(string $name, ?bool $default = null): ?bool;

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     *
     * @param mixed[] $default
     * @return mixed[]
     */
    public function get_array(string $name, ?array $default = []): ?array;
    //@} /*--------------------------------------------------------------------------------------------*/
}


/**
 * Class BaseConfig
 *
 * Common methods for manipulating the list, loading and saving is
 * left to the concrete implementation
 */
abstract class BaseConfig implements Config
{
    /** @var array<string, mixed> */
    public array $values = [];

    public function set_int(string $name, ?int $value): void
    {
        $this->values[$name] = is_null($value) ? null : $value;
        $this->save($name);
    }

    public function set_float(string $name, ?float $value): void
    {
        $this->values[$name] = $value;
        $this->save($name);
    }

    public function set_string(string $name, ?string $value): void
    {
        $this->values[$name] = $value;
        $this->save($name);
    }

    public function set_bool(string $name, ?bool $value): void
    {
        $this->values[$name] = $value ? 'Y' : 'N';
        $this->save($name);
    }

    public function set_array(string $name, ?array $value): void
    {
        if ($value != null) {
            $this->values[$name] = implode(",", $value);
        } else {
            $this->values[$name] = null;
        }
        $this->save($name);
    }

    public function set_default_int(string $name, int $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = $value;
        }
    }

    public function set_default_float(string $name, float $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = $value;
        }
    }

    public function set_default_string(string $name, string $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = $value;
        }
    }

    public function set_default_bool(string $name, bool $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = $value ? 'Y' : 'N';
        }
    }

    public function set_default_array(string $name, array $value): void
    {
        if (is_null($this->get($name))) {
            $this->values[$name] = implode(",", $value);
        }
    }

    /**
     * @template T of int|null
     * @param T $default
     * @return T|int
     */
    public function get_int(string $name, ?int $default = null): ?int
    {
        return (int)($this->get($name, $default));
    }

    /**
     * @template T of float|null
     * @param T $default
     * @return T|float
     */
    public function get_float(string $name, ?float $default = null): ?float
    {
        return (float)($this->get($name, $default));
    }

    /**
     * @template T of string|null
     * @param T $default
     * @return T|string
     */
    public function get_string(string $name, ?string $default = null): ?string
    {
        $val = $this->get($name, $default);
        if (!is_string($val) && !is_null($val)) {
            throw new ServerError("$name is not a string: $val");
        }
        return $val;
    }

    /**
     * @template T of bool|null
     * @param T $default
     * @return T|bool
     */
    public function get_bool(string $name, ?bool $default = null): ?bool
    {
        return bool_escape($this->get($name, $default));
    }

    /**
     * @template T of array<string>|null
     * @param T $default
     * @return T|array<string>
     */
    public function get_array(string $name, ?array $default = null): ?array
    {
        $val = $this->get($name);
        if(is_null($val)) {
            return $default;
        }
        if(empty($val)) {
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
 * Class DatabaseConfig
 *
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
class DatabaseConfig extends BaseConfig
{
    private Database $database;
    private string $table_name;
    private ?string $sub_column;
    private ?string $sub_value;
    private string $cache_name;

    public function __construct(
        Database $database,
        string $table_name = "config",
        string $sub_column = null,
        string $sub_value = null
    ) {
        global $cache;

        $this->database = $database;
        $this->table_name = $table_name;
        $this->sub_value = $sub_value;
        $this->sub_column = $sub_column;
        $this->cache_name = empty($sub_value) ? "config" : "config_{$sub_column}_{$sub_value}";
        $this->values = cache_get_or_set($this->cache_name, fn () => $this->get_values());
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
            $values[$row["name"]] = $row["value"];
        }

        return $values;
    }

    public function save(string $name = null): void
    {
        global $cache;

        if (is_null($name)) {
            reset($this->values); // rewind the array to the first element
            foreach ($this->values as $name => $value) {
                $this->save($name);
            }
        } else {
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
