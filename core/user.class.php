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
	var $class;

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
		global $_user_classes;

		$this->id = int_escape($row['id']);
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->passhash = $row['pass'];
		$this->class = $_user_classes[$row["class"]];
	}

	public static function by_session(/*string*/ $name, /*string*/ $session) {
		global $config, $database;
		$row = $database->cache->get("user-session-$name-$session");
		if(!$row) {
			if($database->get_driver_name() === "mysql") {
				$query = "SELECT * FROM users WHERE name = :name AND md5(concat(pass, :ip)) = :sess";
			}
			else {
				$query = "SELECT * FROM users WHERE name = :name AND md5(pass || :ip) = :sess";
			}
			$row = $database->get_row($query, array("name"=>$name, "ip"=>get_session_ip($config), "sess"=>$session));
			$database->cache->set("user-session-$name-$session", $row, 600);
		}
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
		if($id === 1) $database->cache->set('user-id:'.$id, $row, 600);
		return is_null($row) ? null : new User($row);
	}

	public static function by_name(/*string*/ $name) {
		assert(is_string($name));
		global $database;
		$row = $database->get_row($database->scoreql_to_sql("SELECT * FROM users WHERE SCORE_STRNORM(name) = SCORE_STRNORM(:name)"), array("name"=>$name));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name_and_hash(/*string*/ $name, /*string*/ $hash) {
		assert(is_string($name));
		assert(is_string($hash));
		assert(strlen($hash) == 32);
		global $database;
		$row = $database->get_row($database->scoreql_to_sql("SELECT * FROM users WHERE SCORE_STRNORM(name) = SCORE_STRNORM(:name) AND pass = :hash"), array("name"=>$name, "hash"=>$hash));
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
		return $this->class->can($ability);
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
		return ($this->class->name === "admin");
	}

	public function set_class(/*string*/ $class) {
		assert(is_string($class));
		global $database;
		$database->Execute("UPDATE users SET class=:class WHERE id=:id", array("class"=>$class, "id"=>$this->id));
		log_info("core-user", 'Set class for '.$this->name.' to '.$class);
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
				$d = urlencode($config->get_string("avatar_gravatar_default"));
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
