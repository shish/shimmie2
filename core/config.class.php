<?php
/*
 * an abstract interface for altering a name:value pair list
 */
interface Config {
	public function save($name=null);

	public function set_int($name, $value);
	public function set_string($name, $value);
	public function set_bool($name, $value);

	public function set_default_int($name, $value);
	public function set_default_string($name, $value);
	public function set_default_bool($name, $value);

	public function get_int($name, $default=null);
	public function get_string($name, $default=null);
	public function get_bool($name, $default=null);
}


/*
 * Common methods for manipulating the list, loading and saving is
 * left to the concrete implementation
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

	public function get_int($name, $default=null) {
		return (int)($this->get($name, $default));
	}
	public function get_string($name, $default=null) {
		return $this->get($name, $default);
	}
	public function get_bool($name, $default=null) {
		return ($this->get($name, $default) == 'Y');
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


/*
 * A class for easy access to the 'config' table, can always be accessed
 * through "global $config;"
 */
class DatabaseConfig extends BaseConfig {
	var $values = array();
	var $database = null;

	/*
	 * Load the config table from a database
	 */
	public function DatabaseConfig($database) {
		$this->database = $database;
		$this->values = $this->database->db->GetAssoc("SELECT name, value FROM config");
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
			// does "or update" work with sqlite / postgres?
			$this->database->db->StartTrans();
			$this->database->Execute("DELETE FROM config WHERE name = ?", array($name));
			$this->database->Execute("INSERT INTO config VALUES (?, ?)", array($name, $this->values[$name]));
			$this->database->db->CommitTrans();
		}
	}
}
?>
