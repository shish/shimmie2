<?php
/**
 * @package SCore
 */

/**
 * an abstract interface for altering a name:value pair list
 */
interface Config {
	public function save($name=null);

	public function set_int($name, $value);
	public function set_string($name, $value);
	public function set_bool($name, $value);
	public function set_array($name, $value);

	public function set_default_int($name, $value);
	public function set_default_string($name, $value);
	public function set_default_bool($name, $value);
	public function set_default_array($name, $value);

	public function get_int($name, $default=null);
	public function get_string($name, $default=null);
	public function get_bool($name, $default=null);
	public function get_array($name, $default=array());
}


/**
 * Common methods for manipulating the list, loading and saving is
 * left to the concrete implementation
 *
 * @ignore
 */
abstract class BaseConfig implements Config {
	var $values = array();

	public function set_int($name, $value) {
		$this->values[$name] = parse_shorthand_int($value);
		$this->save($name);
	}
	public function set_string($name, $value) {
		$this->values[$name] = $value;
		$this->save($name);
	}
	public function set_bool($name, $value) {
		$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		$this->save($name);
	}
	public function set_array($name, $value) {
		assert(is_array($value));
		$this->values[$name] = implode(",", $value);
		$this->save($name);
	}

	public function set_default_int($name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = parse_shorthand_int($value);
		}
	}
	public function set_default_string($name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = $value;
		}
	}
	public function set_default_bool($name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		}
	}
	public function set_default_array($name, $value) {
		assert(is_array($value));
		if(is_null($this->get($name))) {
			$this->values[$name] = implode(",", $value);
		}
	}

	public function get_int($name, $default=null) {
		return (int)($this->get($name, $default));
	}
	public function get_string($name, $default=null) {
		return $this->get($name, $default);
	}
	public function get_bool($name, $default=null) {
		return ($this->get($name, $default) == 'Y');
	}
	public function get_array($name, $default=array()) {
		return explode(",", $this->get($name, ""));
	}

	private function get($name, $default=null) {
		if(isset($this->values[$name])) {
			return $this->values[$name];
		}
		else {
			return $default;
		}
	}
}


/**
 * Loads the config list from a PHP file; the file should be in the format:
 *
 *  <?php
 *  $config['foo'] = "bar";
 *  $config['baz'] = "qux";
 *  ?>
 *
 * @ignore
 */
class StaticConfig extends BaseConfig {
	public function __construct($filename) {
		if(file_exists($filename)) {
			require_once $filename;
			if(isset($config)) {
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

	public function save($name=null) {
		// static config is static
	}
}


/**
 * Loads the config list from a table in a given database, the table should
 * be called config and have the schema:
 *
 *  CREATE TABLE config(
 *      name VARCHAR(255) NOT NULL,
 *      value TEXT
 *  );
 *
 * @ignore
 */
class DatabaseConfig extends BaseConfig {
	var $database = null;

	/*
	 * Load the config table from a database
	 */
	public function DatabaseConfig($database) {
		$this->database = $database;

		$cached = $this->database->cache->get("config");
		if($cached) {
			$this->values = $cached;
		}
		else {
			$this->values = $this->database->db->GetAssoc("SELECT name, value FROM config");
			$this->database->cache->set("config", $this->values);
		}
	}

	/*
	 * Save the current values as the new config table
	 */
	public function save($name=null) {
		if(is_null($name)) {
			foreach($this->values as $name => $value) {
				$this->save($name);
			}
		}
		else {
			$this->database->Execute("DELETE FROM config WHERE name = ?", array($name));
			$this->database->Execute("INSERT INTO config VALUES (?, ?)", array($name, $this->values[$name]));
		}
		$this->database->cache->delete("config");
	}
}
?>
