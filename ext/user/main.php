<?php

class UserBlockBuildingEvent extends Event {
	var $parts = array();
	var $user = null;

	public function UserBlockBuildingEvent($user) {
		$this->user = $user;
	}

	public function add_link($name, $link, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = "<a href='$link'>$name</a>";
	}
}

class UserPageBuildingEvent extends Event {
	var $display_user;

	public function __construct(RequestContext $context, User $display_user) {
		parent::__construct($context);
		$this->display_user = $display_user;
	}
}

class UserCreationEvent extends Event {
	var $username;
	var $password;
	var $email;

	public function __construct(RequestContext $context, $name, $pass, $email) {
		parent::__construct($context);
		$this->username = $name;
		$this->password = $pass;
		$this->email = $email;
	}
}

class UserCreationException extends SCoreException {}

class UserPage implements Extension {
	var $theme;

// event handling {{{
	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			$event->context->config->set_default_bool("login_signup_enabled", true);
			$event->context->config->set_default_int("login_memory", 365);
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("user_admin")) {
			$user = $event->context->user;
			$database = $event->context->database;
			$config = $event->context->config;
			$page = $event->context->page;

			if($event->get_arg(0) == "login") {
				if(isset($_POST['user']) && isset($_POST['pass'])) {
					$this->login($page);
				}
				else {
					$this->theme->display_login_page($page);
				}
			}
			else if($event->get_arg(0) == "logout") {
				setcookie("shm_session", "", time()+60*60*24*$config->get_int('login_memory'), "/");
				$page->set_mode("redirect");
				$page->set_redirect(make_link());
			}
			else if($event->get_arg(0) == "change_pass") {
				$this->change_password_wrapper($page);
			}
			else if($event->get_arg(0) == "create") {
				if(!$config->get_bool("login_signup_enabled")) {
					$this->theme->display_signups_disabled($page);
				}
				else if(!isset($_POST['name'])) {
					$this->theme->display_signup_page($page);
				}
				else if($_POST['pass1'] != $_POST['pass2']) {
					$this->theme->display_error($page, "Password Mismatch", "Passwords don't match");
				}
				else {
					try {
						$uce = new UserCreationEvent($event->context, $_POST['name'], $_POST['pass1'], $_POST['email']);
						send_event($uce);
						$this->set_login_cookie($uce->username, $uce->password);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("user"));
					}
					catch(UserCreationException $ex) {
						$this->theme->display_error($page, "User Creation Error", $ex->getMessage());
					}
				}
			}
			else if($event->get_arg(0) == "set_more") {
				$this->set_more_wrapper($page);
			}
		}
		if(($event instanceof PageRequestEvent) && $event->page_matches("user")) {
			$user = $event->context->user;
			$config = $event->context->config;
			$database = $event->context->database;
			$page = $event->context->page;

			$display_user = ($event->count_args() == 0) ? $user : User::by_name($config, $database, $event->get_arg(0));
			if(!is_null($display_user)) {
				send_event(new UserPageBuildingEvent($event->context, $display_user));
			}
			else {
				$this->theme->display_error($page, "No Such User",
					"If you typed the ID by hand, try again; if you came from a link on this ".
					"site, it might be bug report time...");
			}
		}

		if($event instanceof UserPageBuildingEvent) {
			global $user;
			global $config;
			$this->theme->display_user_page($event->context->page, $event->display_user, $user);
			if($user->id == $event->display_user->id) {
				$ubbe = new UserBlockBuildingEvent($event->display_user);
				send_event($ubbe);
				ksort($ubbe->parts);
				$this->theme->display_user_links($event->context->page, $event->context->user, $ubbe->parts);
			}
			if(($user->is_admin() || $user->id == $event->display_user->id) && ($user->id != $config->get_int('anon_id'))) {
				$this->theme->display_ip_list($event->context->page, $this->count_upload_ips($event->display_user), $this->count_comment_ips($event->display_user));
			}
		}

		// user info is shown on all pages
		if($event instanceof PageRequestEvent) {
			$user = $event->context->user;
			$database = $event->context->database;
			$page = $event->context->page;

			if($user->is_anonymous()) {
				$this->theme->display_login_block($page);
			}
			else {
				$ubbe = new UserBlockBuildingEvent($user);
				send_event($ubbe);
				ksort($ubbe->parts);
				$this->theme->display_user_block($page, $user, $ubbe->parts);
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("User Options");
			$sb->add_bool_option("login_signup_enabled", "Allow new signups: ");
			$sb->add_longtext_option("login_tac", "<br>Terms &amp; Conditions:<br>");
			$event->panel->add_block($sb);
		}

		if($event instanceof UserBlockBuildingEvent) {
			$event->add_link("User Config", make_link("user"));
			$event->add_link("Log Out", make_link("user_admin/logout"), 99);
		}

		if($event instanceof UserCreationEvent) {
			if($this->check_user_creation($event)) $this->create_user($event);
		}

		if($event instanceof SearchTermParseEvent) {
			$matches = array();
			if(preg_match("/^(poster|user)=(.*)$/i", $event->term, $matches)) {
				global $config;
				global $database;
				$user = User::by_name($config, $database, $matches[2]);
				if(!is_null($user)) {
					$user_id = $user->id;
				}
				else {
					$user_id = -1;
				}
				$event->add_querylet(new Querylet("images.owner_id = $user_id"));
			}
			else if(preg_match("/^(poster|user)_id=([0-9]+)$/i", $event->term, $matches)) {
				$user_id = int_escape($matches[2]);
				$event->add_querylet(new Querylet("images.owner_id = $user_id"));
			}
		}
	}
// }}}
// Things done *with* the user {{{
	private function login($page)  {
		global $database;
		global $config;
		global $user;

		$name = $_POST['user'];
		$pass = $_POST['pass'];
		$hash = md5(strtolower($name) . $pass);

		$duser = User::by_name_and_hash($config, $database, $name, $hash);
		if(!is_null($duser)) {
			$user = $duser;
			$this->set_login_cookie($name, $pass);
			$page->set_mode("redirect");
			$page->set_redirect(make_link("user"));
		}
		else {
			$this->theme->display_error($page, "Error", "No user with those details was found");
		}
	}

	private function check_user_creation($event) {
		$name = $event->username;
		$pass = $event->password;
		$email = $event->email;

		global $database;

		if(strlen($name) < 1) {
			throw new UserCreationException("Username must be at least 1 character");
		}
		else if(!preg_match('/^[a-zA-Z0-9-_]+$/', $name)) {
			throw new UserCreationException(
					"Username contains invalid characters. Allowed characters are ".
					"letters, numbers, dash, and underscore");
		}
		else if($database->db->GetRow("SELECT * FROM users WHERE name = ?", array($name))) {
			throw new UserCreationException("That username is already taken");
		}
	}

	private function create_user($event) {
		global $database;

		$hash = md5(strtolower($event->username) . $event->password);
		$email = (!empty($event->email)) ? $event->email : null;

		$database->Execute(
				"INSERT INTO users (name, pass, joindate, email) VALUES (?, ?, now(), ?)",
				array($event->username, $hash, $email));
	}

	private function set_login_cookie($name, $pass) {
		global $config;

		$addr = get_session_ip($config);
		$hash = md5(strtolower($name) . $pass);

		setcookie("shm_user", $name,
				time()+60*60*24*365, '/');
		setcookie("shm_session", md5($hash.$addr),
				time()+60*60*24*$config->get_int('login_memory'), '/');
	}
//}}}
// Things done *to* the user {{{
	private function change_password_wrapper($page) {
		global $user;
		global $config;
		global $database;

		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_block(new NavBlock());
		if($user->is_anonymous()) {
			$page->add_block(new Block("Error", "You aren't logged in"));
		}
		else if(isset($_POST['id']) && isset($_POST['name']) &&
				isset($_POST['pass1']) && isset($_POST['pass2'])) {
			$name = $_POST['name'];
			$id = $_POST['id'];
			$pass1 = $_POST['pass1'];
			$pass2 = $_POST['pass2'];

			$duser = User::by_id($config, $database, $id);

			if((!$user->is_admin()) && ($duser->name != $user->name)) {
				$page->add_block(new Block("Error",
						"You need to be an admin to change other people's passwords"));
			}
			else if($pass1 != $pass2) {
				$page->add_block(new Block("Error", "Passwords don't match"));
			}
			else {
				global $config;

				// FIXME: send_event()
				$duser->set_password($pass1);

				if($id == $user->id) {
					$this->set_login_cookie($duser->name, $pass1);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("user"));
				}
				else {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("user/{$user->name}"));
				}
			}
		}
	}

	private function set_more_wrapper($page) {
		global $user;
		global $config;
		global $database;

		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_block(new NavBlock());
		if(!$user->is_admin()) {
			$page->add_block(new Block("Not Admin", "Only admins can edit accounts"));
		}
		else if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
			$page->add_block(new Block("No ID Specified",
					"You need to specify the account number to edit"));
		}
		else {
			$admin = (isset($_POST['admin']) && ($_POST['admin'] == "on"));

			$duser = User::by_id($config, $database, $_POST['id']);
			$duser->set_admin($admin);

			$page->set_mode("redirect");
			if($duser->id == $user->id) {
				$page->set_redirect(make_link("user"));
			}
			else {
				$page->set_redirect(make_link("user/{$duser->name}"));
			}
		}
	}
// }}}
// ips {{{
	private function count_upload_ips($duser) {
		global $database;
		$rows = $database->db->GetAssoc("
				SELECT
					owner_ip,
					COUNT(images.id) AS count,
					MAX(posted) AS most_recent
				FROM images
				WHERE owner_id=?
				GROUP BY owner_ip
				ORDER BY most_recent DESC", array($duser->id), false, true);
		return $rows;
	}
	private function count_comment_ips($duser) {
		global $database;
		$rows = $database->db->GetAssoc("
				SELECT
					owner_ip,
					COUNT(comments.id) AS count,
					MAX(posted) AS most_recent
				FROM comments
				WHERE owner_id=?
				GROUP BY owner_ip
				ORDER BY most_recent DESC", array($duser->id), false, true);
		return $rows;
	}
// }}}
}
add_event_listener(new UserPage());
?>
