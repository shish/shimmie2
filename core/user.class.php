<?php
/*
 * An object representing a row in the "users" table.
 */
class User {
	var $config;
	var $database;

	var $id;
	var $name;
	var $email;
	var $join_date;
	var $admin;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	* Initialisation                                               *
	*                                                              *
	* User objects shouldn't be created directly, they should be   *
	* fetched from the database like so:                           *
	*                                                              *
	*    $user = User::by_name($config, $database, "bob");         *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	public function User($row) {
		$this->id = int_escape($row['id']);
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->admin = ($row['admin'] == 'Y');
	}

	public static function by_session($name, $session) {
		global $config, $database;
		$row = $database->get_row(
				"SELECT * FROM users WHERE name = ? AND md5(concat(pass, ?)) = ?",
				array($name, get_session_ip($config), $session)
		);
		return is_null($row) ? null : new User($row);
	}

	public static function by_id($id) {
		assert(is_numeric($id));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE id = ?", array($id));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name($name) {
		assert(is_string($name));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = ?", array($name));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name_and_hash($name, $hash) {
		assert(is_string($name));
		assert(is_string($hash));
		assert(strlen($hash) == 32);
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = ? AND pass = ?", array($name, $hash));
		return is_null($row) ? null : new User($row);
	}


	/*
	 * useful user object functions start here
	 */


	public function is_anonymous() {
		global $config;
		return ($this->id == $config->get_int('anon_id'));
	}

	public function is_admin() {
		return $this->admin;
	}

	public function set_admin($admin) {
		assert(is_bool($admin));
		global $database;
		$yn = $admin ? 'Y' : 'N';
		$database->Execute("UPDATE users SET admin=? WHERE id=?", array($yn, $this->id));
		log_info("core-user", "Made {$this->name} admin=$yn");
	}

	public function set_password($password) {
		global $database;
		$hash = md5(strtolower($this->name) . $password);
		$database->Execute("UPDATE users SET pass=? WHERE id=?", array($hash, $this->id));
		log_info("core-user", "Set password for {$this->name}");
	}
}
?>
