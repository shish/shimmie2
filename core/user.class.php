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

	public function User(Config $config, Database $database, $row) {
		$this->config = $config;
		$this->database = $database;

		$this->id = int_escape($row['id']);
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->admin = ($row['admin'] == 'Y');
	}

	public static function by_session(Config $config, Database $database, $name, $session) {
		$row = $database->get_row(
				"SELECT * FROM users WHERE name = ? AND md5(concat(pass, ?)) = ?",
				array($name, get_session_ip($config), $session)
		);
		return is_null($row) ? null : new User($config, $database, $row);
	}

	public static function by_id(Config $config, Database $database, $id) {
		assert(is_numeric($id));
		$row = $database->get_row("SELECT * FROM users WHERE id = ?", array($id));
		return is_null($row) ? null : new User($config, $database, $row);
	}

	public static function by_name(Config $config, Database $database, $name) {
		assert(is_string($name));
		$row = $database->get_row("SELECT * FROM users WHERE name = ?", array($name));
		return is_null($row) ? null : new User($config, $database, $row);
	}

	public static function by_name_and_hash(Config $config, Database $database, $name, $hash) {
		assert(is_string($name));
		assert(is_string($hash));
		assert(strlen($hash) == 32);
		$row = $database->get_row("SELECT * FROM users WHERE name = ? AND pass = ?", array($name, $hash));
		return is_null($row) ? null : new User($config, $database, $row);
	}


	/*
	 * useful user object functions start here
	 */


	public function is_anonymous() {
		return ($this->id == $this->config->get_int('anon_id'));
	}

	public function is_admin() {
		return $this->admin;
	}

	public function set_admin($admin) {
		assert(is_bool($admin));
		$yn = $admin ? 'Y' : 'N';
		$this->database->Execute("UPDATE users SET admin=? WHERE id=?", array($yn, $this->id));
	}

	public function set_password($password) {
		$hash = md5(strtolower($this->name) . $password);
		$this->database->Execute("UPDATE users SET pass=? WHERE id=?", array($hash, $this->id));
	}

	public function get_days_old() {
		return 0; // FIXME calculate this
	}

	public function get_image_count() {
		global $database;
		return $database->db->GetOne("SELECT COUNT(*) AS count FROM images WHERE owner_id=?", array($this->id));
	}

	public function get_comment_count() {
		global $database;
		return $database->db->GetOne("SELECT COUNT(*) AS count FROM comments WHERE owner_id=?", array($this->id));
	}
}
?>
