<?php
/**
 * an abstract interface for altering a name:value pair list
 */
interface Config {
	/**
	 * Save the list of name:value pairs to wherever they came from,
	 * so that the next time a page is loaded it will use the new
	 * configuration
	 */
	public function save(/*string*/ $name=null);

	/** @name set_*
	 * Set a configuration option to a new value, regardless
	 * of what the value is at the moment
	 */
	//@{
	public function set_int(/*string*/ $name, $value);
	public function set_string(/*string*/ $name, $value);
	public function set_bool(/*string*/ $name, $value);
	public function set_array(/*string*/ $name, $value);
	//@}

	/** @name set_default_*
	 * Set a configuration option to a new value, if there is no
	 * value currently. Extensions should generally call these
	 * from their InitExtEvent handlers. This has the advantage
	 * that the values will show up in the "advanced" setup page
	 * where they can be modified, while calling get_* with a
	 * "default" paramater won't show up.
	 */
	//@{
	public function set_default_int(/*string*/ $name, $value);
	public function set_default_string(/*string*/ $name, $value);
	public function set_default_bool(/*string*/ $name, $value);
	public function set_default_array(/*string*/ $name, $value);
	//@}

	/** @name get_*
	 * pick a value out of the table by name, cast to the
	 * appropritate data type
	 */
	//@{
	public function get_int(/*string*/ $name, $default=null);
	public function get_string(/*string*/ $name, $default=null);
	public function get_bool(/*string*/ $name, $default=null);
	public function get_array(/*string*/ $name, $default=array());
	//@}
}


/**
 * Common methods for manipulating the list, loading and saving is
 * left to the concrete implementation
 */
abstract class BaseConfig implements Config {
	var $values = array();

	public function set_int(/*string*/ $name, $value) {
		$this->values[$name] = parse_shorthand_int($value);
		$this->save($name);
	}
	public function set_string(/*string*/ $name, $value) {
		$this->values[$name] = $value;
		$this->save($name);
	}
	public function set_bool(/*string*/ $name, $value) {
		$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		$this->save($name);
	}
	public function set_array(/*string*/ $name, $value) {
		assert(is_array($value));
		$this->values[$name] = implode(",", $value);
		$this->save($name);
	}

	public function set_default_int(/*string*/ $name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = parse_shorthand_int($value);
		}
	}
	public function set_default_string(/*string*/ $name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = $value;
		}
	}
	public function set_default_bool(/*string*/ $name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		}
	}
	public function set_default_array(/*string*/ $name, $value) {
		assert(is_array($value));
		if(is_null($this->get($name))) {
			$this->values[$name] = implode(",", $value);
		}
	}

	public function get_int(/*string*/ $name, $default=null) {
		return (int)($this->get($name, $default));
	}
	public function get_string(/*string*/ $name, $default=null) {
		return $this->get($name, $default);
	}
	public function get_bool(/*string*/ $name, $default=null) {
		return bool_escape($this->get($name, $default));
	}
	public function get_array(/*string*/ $name, $default=array()) {
		return explode(",", $this->get($name, ""));
	}

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
 * Loads the config list from a PHP file; the file should be in the format:
 *
 *  <?php
 *  $config['foo'] = "bar";
 *  $config['baz'] = "qux";
 *  ?>
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

	public function save(/*string*/ $name=null) {
		// static config is static
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
class DatabaseConfig extends BaseConfig {
	var $database = null;

	/*
	 * Load the config table from a database
	 */
	public function DatabaseConfig(Database $database) {
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

	/*
	 * Save the current values as the new config table
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
		$this->database->cache->delete("config");
	}
}
?>
