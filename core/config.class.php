<?php

/**
 * Interface Config
 *
 * An abstract interface for altering a name:value pair list.
 */
interface Config {
	/**
	 * Save the list of name:value pairs to wherever they came from,
	 * so that the next time a page is loaded it will use the new
	 * configuration.
	 *
	 * @param null|string $name
	 * @return mixed|void
	 */
	public function save(/*string*/ $name=null);

	//@{ /*--------------------------------- SET ------------------------------------------------------*/
	/**
	 * Set a configuration option to a new value, regardless of what the value is at the moment.
	 * @param string $name
	 * @param null|int $value
	 * @return void
	 */
	public function set_int(/*string*/ $name, $value);

	/**
	 * Set a configuration option to a new value, regardless of what the value is at the moment.
	 * @param string $name
	 * @param null|string $value
	 * @return void
	 */
	public function set_string(/*string*/ $name, $value);

	/**
	 * Set a configuration option to a new value, regardless of what the value is at the moment.
	 * @param string $name
	 * @param null|bool|string $value
	 * @return void
	 */
	public function set_bool(/*string*/ $name, $value);

	/**
	 * Set a configuration option to a new value, regardless of what the value is at the moment.
	 * @param string $name
	 * @param array $value
	 * @return void
	 */
	public function set_array(/*string*/ $name, $value);
	//@} /*--------------------------------------------------------------------------------------------*/

	//@{ /*-------------------------------- SET DEFAULT -----------------------------------------------*/
	/**
	 * Set a configuration option to a new value, if there is no value currently.
	 *
	 * Extensions should generally call these from their InitExtEvent handlers.
	 * This has the advantage that the values will show up in the "advanced" setup
	 * page where they can be modified, while calling get_* with a "default"
	 * parameter won't show up.
	 *
	 * @param string $name
	 * @param int $value
	 * @return void
	 */
	public function set_default_int(/*string*/ $name, $value);

	/**
	 * Set a configuration option to a new value, if there is no value currently.
	 *
	 * Extensions should generally call these from their InitExtEvent handlers.
	 * This has the advantage that the values will show up in the "advanced" setup
	 * page where they can be modified, while calling get_* with a "default"
	 * parameter won't show up.
	 *
	 * @param string $name
	 * @param string|null $value
	 * @return void
	 */
	public function set_default_string(/*string*/ $name, $value);

	/**
	 * Set a configuration option to a new value, if there is no value currently.
	 *
	 * Extensions should generally call these from their InitExtEvent handlers.
	 * This has the advantage that the values will show up in the "advanced" setup
	 * page where they can be modified, while calling get_* with a "default"
	 * parameter won't show up.
	 *
	 * @param string $name
	 * @param bool $value
	 * @return void
	 */
	public function set_default_bool(/*string*/ $name, /*bool*/ $value);

	/**
	 * Set a configuration option to a new value, if there is no value currently.
	 *
	 * Extensions should generally call these from their InitExtEvent handlers.
	 * This has the advantage that the values will show up in the "advanced" setup
	 * page where they can be modified, while calling get_* with a "default"
	 * parameter won't show up.
	 *
	 * @param string $name
	 * @param array $value
	 * @return void
	 */
	public function set_default_array(/*string*/ $name, $value);
	//@} /*--------------------------------------------------------------------------------------------*/

	//@{ /*--------------------------------- GET ------------------------------------------------------*/
	/**
	 * Pick a value out of the table by name, cast to the appropriate data type.
	 * @param string $name
	 * @param null|int $default
	 * @return int
	 */
	public function get_int(/*string*/ $name, $default=null);

	/**
	 * Pick a value out of the table by name, cast to the appropriate data type.
	 * @param string $name
	 * @param null|string $default
	 * @return string
	 */
	public function get_string(/*string*/ $name, $default=null);

	/**
	 * Pick a value out of the table by name, cast to the appropriate data type.
	 * @param string $name
	 * @param null|bool|string $default
	 * @return bool
	 */
	public function get_bool(/*string*/ $name, $default=null);

	/**
	 * Pick a value out of the table by name, cast to the appropriate data type.
	 * @param string $name
	 * @param array|null $default
	 * @return array
	 */
	public function get_array(/*string*/ $name, $default=array());
	//@} /*--------------------------------------------------------------------------------------------*/
}


/**
 * Class BaseConfig
 *
 * Common methods for manipulating the list, loading and saving is
 * left to the concrete implementation
 */
abstract class BaseConfig implements Config {
	var $values = array();

	/**
	 * @param string $name
	 * @param int|null $value
	 * @return void
	 */
	public function set_int(/*string*/ $name, $value) {
		$this->values[$name] = parse_shorthand_int($value);
		$this->save($name);
	}

	/**
	 * @param string $name
	 * @param null|string $value
	 * @return void
	 */
	public function set_string(/*string*/ $name, $value) {
		$this->values[$name] = $value;
		$this->save($name);
	}

	/**
	 * @param string $name
	 * @param bool|null|string $value
	 * @return void
	 */
	public function set_bool(/*string*/ $name, $value) {
		$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		$this->save($name);
	}

	/**
	 * @param string $name
	 * @param array $value
	 * @return void
	 */
	public function set_array(/*string*/ $name, $value) {
		assert(isset($value) && is_array($value));
		$this->values[$name] = implode(",", $value);
		$this->save($name);
	}

	/**
	 * @param string $name
	 * @param int $value
	 * @return void
	 */
	public function set_default_int(/*string*/ $name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = parse_shorthand_int($value);
		}
	}

	/**
	 * @param string $name
	 * @param null|string $value
	 * @return void
	 */
	public function set_default_string(/*string*/ $name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = $value;
		}
	}

	/**
	 * @param string $name
	 * @param bool $value
	 * @return void
	 */
	public function set_default_bool(/*string*/ $name, /*bool*/ $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		}
	}

	/**
	 * @param string $name
	 * @param array $value
	 * @return void
	 */
	public function set_default_array(/*string*/ $name, $value) {
		assert(isset($value) && is_array($value));
		if(is_null($this->get($name))) {
			$this->values[$name] = implode(",", $value);
		}
	}

	/**
	 * @param string $name
	 * @param null|int $default
	 * @return int
	 */
	public function get_int(/*string*/ $name, $default=null) {
		return (int)($this->get($name, $default));
	}

	/**
	 * @param string $name
	 * @param null|string $default
	 * @return null|string
	 */
	public function get_string(/*string*/ $name, $default=null) {
		return $this->get($name, $default);
	}

	/**
	 * @param string $name
	 * @param null|bool|string $default
	 * @return bool
	 */
	public function get_bool(/*string*/ $name, $default=null) {
		return bool_escape($this->get($name, $default));
	}

	/**
	 * @param string $name
	 * @param array $default
	 * @return array
	 */
	public function get_array(/*string*/ $name, $default=array()) {
		return explode(",", $this->get($name, ""));
	}

	/**
	 * @param string $name
	 * @param null|mixed $default
	 * @return null|mixed
	 */
	private function get(/*string*/ $name, $default=null) {
		if(isset($this->values[$name])) {
			return $this->values[$name];
		}
		else {
			return $default;
		}
	}
}


/**
 * Class HardcodeConfig
 *
 * For testing, mostly.
 */
class HardcodeConfig extends BaseConfig {
	public function __construct($dict) {
		$this->values = $dict;
	}

	/**
	 * @param null|string $name
	 * @return mixed|void
	 */
	public function save(/*string*/ $name=null) {
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
class StaticConfig extends BaseConfig {
	/**
	 * @param string $filename
	 * @throws Exception
	 */
	public function __construct($filename) {
		if(file_exists($filename)) {
			$config = array();
			require_once $filename;
			if(!empty($config)) {
				$this->values = $config;
			}
			else {
				throw new Exception("Config file '$filename' doesn't contain any config");
			}
		}
		else {
			throw new Exception("Config file '$filename' missing");
		}
	}

	/**
	 * @param null|string $name
	 * @return mixed|void
	 */
	public function save(/*string*/ $name=null) {
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
class DatabaseConfig extends BaseConfig {
	/** @var \Database|null  */
	var $database = null;

	/**
	 * Load the config table from a database.
	 *
	 * @param Database $database
	 */
	public function __construct(Database $database) {
		$this->database = $database;

		$cached = $this->database->cache->get("config");
		if($cached) {
			$this->values = $cached;
		}
		else {
			$this->values = array();
			foreach($this->database->get_all("SELECT name, value FROM config") as $row) {
				$this->values[$row["name"]] = $row["value"];
			}
			$this->database->cache->set("config", $this->values);
		}
	}

	/**
	 * Save the current values as the new config table.
	 *
	 * @param null|string $name
	 * @return mixed|void
	 */
	public function save(/*string*/ $name=null) {
		if(is_null($name)) {
			reset($this->values); // rewind the array to the first element
			foreach($this->values as $name => $value) {
				$this->save(/*string*/ $name);
			}
		}
		else {
			$this->database->Execute("DELETE FROM config WHERE name = :name", array("name"=>$name));
			$this->database->Execute("INSERT INTO config VALUES (:name, :value)", array("name"=>$name, "value"=>$this->values[$name]));
		}
		// rather than deleting and having some other request(s) do a thundering
		// herd of race-conditioned updates, just save the updated version once here
		$this->database->cache->set("config", $this->values);
	}
}

/**
 * Class MockConfig
 */
class MockConfig extends HardcodeConfig {
	/**
	 * @param array $config
	 */
	public function __construct($config=array()) {
		$config["db_version"] = "999";
		$config["anon_id"] = "0";
		parent::__construct($config);
	}
}

