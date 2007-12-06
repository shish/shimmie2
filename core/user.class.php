<?php
/*
 * An object representing a row in the "users" table.
 */
class User {
	var $id;
	var $name;
	var $email;
	var $join_date;
	var $days_old;
	var $enabled;
	var $admin;
	
	public function User($row) {
		$this->id = int_escape($row['id']);
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->days_old = $row['days_old'];
		$this->enabled = ($row['enabled'] == 'Y');
		$this->admin = ($row['admin'] == 'Y');
	}

	public function is_anonymous() {
		global $config;
		return ($this->id == $config->get_int('anon_id'));
	}

	public function is_enabled() {
		return $this->enabled;
	}

	public function set_enabled($enabled) {
		global $database;
		
		$yn = $enabled ? 'Y' : 'N';
		$database->Execute("UPDATE users SET enabled=? WHERE id=?", array($yn, $this->id));
	}

	public function is_admin() {
		return $this->admin;
	}

	public function set_admin($admin) {
		global $database;

		$yn = $admin ? 'Y' : 'N';
		$database->Execute("UPDATE users SET admin=? WHERE id=?", array($yn, $this->id));
	}

	public function set_password($password) {
		global $database;

		$hash = md5(strtolower($this->name) . $password);
		$database->Execute("UPDATE users SET pass=? WHERE id=?", array($hash, $this->id));
	}

	public function get_days_old() {
		return $this->days_old;
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
