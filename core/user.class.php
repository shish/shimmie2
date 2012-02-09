<?php
/** @private */
function _new_user($row) {
	return new User($row);
}


/**
 * An object representing a row in the "users" table.
 *
 * The currently logged in user will always be accessable via the global variable $user
 */
class User {
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
	*    $user = User::by_name("bob");                             *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * One will very rarely construct a user directly, more common
	 * would be to use User::by_id, User::by_session, etc
	 */
	public function User($row) {
		$this->id = int_escape($row['id']);
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->admin = ($row['admin'] == 'Y');
		$this->passhash = $row['pass'];
	}

	public static function by_session(/*string*/ $name, /*string*/ $session) {
		global $config, $database;
		if($database->engine->name === "mysql") {
			$query = "SELECT * FROM users WHERE name = :name AND md5(concat(pass, :ip)) = :sess";
		}
		else {
			$query = "SELECT * FROM users WHERE name = :name AND md5(pass || :ip) = :sess";
		}
		$row = $database->get_row($query, array("name"=>$name, "ip"=>get_session_ip($config), "sess"=>$session));
		return is_null($row) ? null : new User($row);
	}

	public static function by_id(/*int*/ $id) {
		assert(is_numeric($id));
		global $database;
		if($id === 1) {
			$cached = $database->cache->get('user-id:'.$id);
			if($cached) return new User($cached);
		}
		$row = $database->get_row("SELECT * FROM users WHERE id = :id", array("id"=>$id));
		if($id === 1) $database->cache->set('user-id:'.$id, $row, 300);
		return is_null($row) ? null : new User($row);
	}

	public static function by_name(/*string*/ $name) {
		assert(is_string($name));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = :name", array("name"=>$name));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name_and_hash(/*string*/ $name, /*string*/ $hash) {
		assert(is_string($name));
		assert(is_string($hash));
		assert(strlen($hash) == 32);
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = :name AND pass = :hash", array("name"=>$name, "hash"=>$hash));
		return is_null($row) ? null : new User($row);
	}

	public static function by_list(/*int*/ $offset, /*int*/ $limit=50) {
		assert(is_numeric($offset));
		assert(is_numeric($limit));
		global $database;
		$rows = $database->get_all("SELECT * FROM users WHERE id >= :start AND id < :end", array("start"=>$offset, "end"=>$offset+$limit));
		return array_map("_new_user", $rows);
	}


	/*
	 * useful user object functions start here
	 */
	public function can($ability) {
		global $config;

		// TODO: make this into an editable database table
		$user_classes = array(
			"anonymous" => array(
				"change_setting" => False,  # web-level settings, eg the config table
				"override_config" => False, # sys-level config, eg config.php
				"big_search" => False,      # more than 3 tags (speed mode only)
				"lock_image" => False,
				"view_ip" => False,         # view IP addresses associated with things
				"ban_ip" => False,
				"change_password" => False,
				"change_user_info" => False,
				"delete_user" => False,
				"delete_image" => False,
				"delete_comment" => False,
				"replace_image" => False,
				"manage_extension_list" => False,
				"manage_alias_list" => False,
				"edit_image_tag" => $config->get_bool("tag_edit_anon"),
				"edit_image_source" => $config->get_bool("source_edit_anon"),
				"edit_image_owner" => False,
				"mass_tag_edit" => False,
			),
			"user" => array(
				"change_setting" => False,
				"override_config" => False,
				"big_search" => True,
				"lock_image" => False,
				"view_ip" => False,
				"ban_ip" => False,
				"change_password" => False,
				"change_user_info" => False,
				"delete_user" => False,
				"delete_image" => False,
				"delete_comment" => False,
				"change_image_owner" => False,
				"replace_image" => False,
				"manage_extension_list" => False,
				"manage_alias_list" => False,
				"edit_image_tag" => True,
				"edit_image_source" => True,
				"edit_image_owner" => False,
				"mass_tag_edit" => False,
			),
			"admin" => array(
				"change_setting" => True,
				"override_config" => True,
				"big_search" => True,
				"lock_image" => True,
				"view_ip" => True,
				"ban_ip" => True,
				"change_password" => True,
				"change_user_info" => True,
				"delete_user" => True,
				"delete_image" => True,
				"delete_comment" => True,
				"replace_image" => True,
				"manage_extension_list" => True,
				"manage_alias_list" => True,
				"edit_image_tag" => True,
				"edit_image_source" => True,
				"edit_image_owner" => True,
				"mass_tag_edit" => True,
			),
		);

		return $user_classes[$this->get_class()][$ability];
	}

	// FIXME: this should be a column in the users table
	public function get_class() {
		if($this->is_admin()) return "admin";
		else if($this->is_logged_in()) return "user";
		else return"anonymous";
	}


	/**
	 * Test if this user is anonymous (not logged in)
	 *
	 * @retval bool
	 */
	public function is_anonymous() {
		global $config;
		return ($this->id === $config->get_int('anon_id'));
	}

	/**
	 * Test if this user is logged in
	 *
	 * @retval bool
	 */
	public function is_logged_in() {
		global $config;
		return ($this->id !== $config->get_int('anon_id'));
	}

	/**
	 * Test if this user is an administrator
	 *
	 * @retval bool
	 */
	public function is_admin() {
		return $this->admin;
	}

	public function set_admin(/*bool*/ $admin) {
		assert(is_bool($admin));
		global $database;
		$yn = $admin ? 'Y' : 'N';
		$database->Execute("UPDATE users SET admin=:yn WHERE id=:id", array("yn"=>$yn, "id"=>$this->id));
		log_info("core-user", 'Made '.$this->name.' admin='.$yn);
	}

	public function set_password(/*string*/ $password) {
		global $database;
		$hash = md5(strtolower($this->name) . $password);
		$database->Execute("UPDATE users SET pass=:hash WHERE id=:id", array("hash"=>$hash, "id"=>$this->id));
		log_info("core-user", 'Set password for '.$this->name);
	}

	public function set_email(/*string*/ $address) {
		global $database;
		$database->Execute("UPDATE users SET email=:email WHERE id=:id", array("email"=>$address, "id"=>$this->id));
		log_info("core-user", 'Set email for '.$this->name);
	}

	/**
	 * Get a snippet of HTML which will render the user's avatar, be that
	 * a local file, a remote file, a gravatar, a something else, etc
	 * @retval String of HTML
	 */
	public function get_avatar_html() {
		// FIXME: configurable
		global $config;
		if($config->get_string("avatar_host") === "gravatar") {
			if(!empty($this->email)) {
				$hash = md5(strtolower($this->email));
				$s = $config->get_string("avatar_gravatar_size");
				$d = $config->get_string("avatar_gravatar_default");
				$r = $config->get_string("avatar_gravatar_rating");
				return "<img class=\"avatar gravatar\" src=\"http://www.gravatar.com/avatar/$hash.jpg?s=$s&d=$d&r=$r\">";
			}
		}
		return "";
	}

	/**
	 * Get an auth token to be used in POST forms
	 *
	 * password = secret, avoid storing directly
	 * passhash = md5(password), so someone who gets to the database can't get passwords
	 * sesskey  = md5(passhash . IP), so if it gets sniffed it can't be used from another IP,
	 *            and it can't be used to get the passhash to generate new sesskeys
	 * authtok  = md5(sesskey, salt), presented to the user in web forms, to make sure that
	 *            the form was generated within the session. Salted and re-hashed so that
	 *            reading a web page from the user's cache doesn't give access to the session key
	 *
	 * @retval String containing auth token (MD5sum)
	 */
	public function get_auth_token() {
		global $config;
		$salt = DATABASE_DSN;
		$addr = get_session_ip($config);
		return md5(md5($this->passhash . $addr) . "salty-csrf-" . $salt);
	}

	public function get_auth_html() {
		$at = $this->get_auth_token();
		return '<input type="hidden" name="auth_token" value="'.$at.'">';
	}

	public function check_auth_token() {
		return (isset($_POST["auth_token"]) && $_POST["auth_token"] == $this->get_auth_token());
	}
}
?>
