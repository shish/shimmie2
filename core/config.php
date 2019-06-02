<?php

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
    public function save(string $name=null): void;

    //@{ /*--------------------------------- SET ------------------------------------------------------*/
    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_int(string $name, ?string $value): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     */
    public function set_string(string $name, ?string $value): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
     * @param null|bool|string $value
     */
    public function set_bool(string $name, $value): void;

    /**
     * Set a configuration option to a new value, regardless of what the value is at the moment.
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
     */
    public function set_default_array(string $name, array $value): void;
    //@} /*--------------------------------------------------------------------------------------------*/

    //@{ /*--------------------------------- GET ------------------------------------------------------*/
    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_int(string $name, ?int $default=null): ?int;

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_string(string $name, ?string $default=null): ?string;

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_bool(string $name, ?bool $default=null): ?bool;

    /**
     * Pick a value out of the table by name, cast to the appropriate data type.
     */
    public function get_array(string $name, ?array $default=[]): ?array;
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
    public $values = [];

    public function set_int(string $name, ?string $value): void
    {
        $this->values[$name] = parse_shorthand_int($value);
        $this->save($name);
    }

    public function set_string(string $name, ?string $value): void
    {
        $this->values[$name] = $value;
        $this->save($name);
    }

    public function set_bool(string $name, $value): void
    {
        $this->values[$name] = bool_escape($value) ? 'Y' : 'N';
        $this->save($name);
    }

    public function set_array(string $name, array $value): void
    {
        $this->values[$name] = implode(",", $value);
        $this->save($name);
    }

    public function set_default_int(string $name, int $value): void
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

    public function get_int(string $name, ?int $default=null): ?int
    {
        return (int)($this->get($name, $default));
    }

    public function get_string(string $name, ?string $default=null): ?string
    {
        return $this->get($name, $default);
    }

    public function get_bool(string $name, ?bool $default=null): ?bool
    {
        return bool_escape($this->get($name, $default));
    }

    public function get_array(string $name, ?array $default=[]): ?array
    {
        return explode(",", $this->get($name, ""));
    }

    private function get(string $name, $default=null)
    {
        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else {
            return $default;
        }
    }
}


/**
 * Class HardcodeConfig
 *
 * For testing, mostly.
 */
class HardcodeConfig extends BaseConfig
{
    public function __construct(array $dict)
    {
        $this->values = $dict;
    }

    public function save(string $name=null): void
    {
        // static config is static
    }
}


/**
 * Class StaticConfig
 *
 * Loads the config list from a PHP file; the file should be in the format:
 *
 *  <?php
 *  $config['foo'] = "bar";
 *  $config['baz'] = "qux";
 *  ?>
 */
class StaticConfig extends BaseConfig
{
    public function __construct(string $filename)
    {
        if (file_exists($filename)) {
            $config = [];
            require_once $filename;
            if (!empty($config)) {
                $this->values = $config;
            } else {
                throw new Exception("Config file '$filename' doesn't contain any config");
            }
        } else {
            throw new Exception("Config file '$filename' missing");
        }
    }

    public function save(string $name=null): void
    {
        // static config is static
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
    /** @var Database  */
    private $database = null;

    public function __construct(Database $database)
    {
        $this->database = $database;

        $cached = $this->database->cache->get("config");
        if ($cached) {
            $this->values = $cached;
        } else {
            $this->values = [];
            foreach ($this->database->get_all("SELECT name, value FROM config") as $row) {
                $this->values[$row["name"]] = $row["value"];
            }
            $this->database->cache->set("config", $this->values);
        }
    }

    public function save(string $name=null): void
    {
        if (is_null($name)) {
            reset($this->values); // rewind the array to the first element
            foreach ($this->values as $name => $value) {
                $this->save($name);
            }
        } else {
            $this->database->Execute("DELETE FROM config WHERE name = :name", ["name"=>$name]);
            $this->database->Execute("INSERT INTO config VALUES (:name, :value)", ["name"=>$name, "value"=>$this->values[$name]]);
        }
        // rather than deleting and having some other request(s) do a thundering
        // herd of race-conditioned updates, just save the updated version once here
        $this->database->cache->set("config", $this->values);
    }
}

/**
 * Class MockConfig
 */
class MockConfig extends HardcodeConfig
{
    public function __construct(array $config=[])
    {
        $config["db_version"] = "999";
        $config["anon_id"] = "0";
        parent::__construct($config);
    }
}
