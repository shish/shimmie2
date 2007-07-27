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
	var $page = null;
	var $user = null;

	public function UserPageBuildingEvent($page, $user) {
		$this->page = $page;
		$this->user = $user;
	}
}

class UserPage extends Extension {
	var $theme;

// event handling {{{
	public function receive_event($event) {
		if(is_null($this->theme)) $this->theme = get_theme_object("user", "UserPageTheme");
		
		if(is_a($event, 'InitExtEvent')) {
			global $config;
			$config->set_default_bool("login_signup_enabled", true);
			$config->set_default_int("login_memory", 365);
		}

		if(is_a($event, 'PageRequestEvent') && ($event->page_name == "user")) {
			global $page;
			global $user;
			global $database;
			global $config;

			if($event->get_arg(0) == "login") {
				if(isset($_POST['user']) && isset($_POST['pass'])) {
					$this->login($event->page);
				}
				else {
					$this->theme->display_login_page($event->page);
				}
			}
			else if($event->get_arg(0) == "logout") {
				setcookie("shm_session", "", time()+60*60*24*$config->get_int('login_memory'), "/");
				$page->set_mode("redirect");
				$page->set_redirect(make_link());
			}
			else if($event->get_arg(0) == "changepass") {
				$this->change_password_wrapper($event->page);
			}
			else if($event->get_arg(0) == "create") {
				$this->create_user_wrapper($event->page);
			}
			else if($event->get_arg(0) == "set_more") {
				$this->set_more_wrapper($event->page);
			}
			else { // view
				$duser = ($event->count_args() == 0) ? $user : $database->get_user_by_name($event->get_arg(0));
				if(!is_null($duser)) {
					send_event(new UserPageBuildingEvent($event->page, $duser));
				}
				else {
					$this->theme->display_error($page, "No Such User", 
						"If you typed the ID by hand, try again; if you came from a link on this ".
						"site, it might be bug report time...");
				}
			}
		}
		
		if(is_a($event, 'UserPageBuildingEvent')) {
			global $user;
			$this->theme->display_user_page($event->page, $event->user, $user);
			if($user->is_admin() || $user->id == $event->user->id) {
				$this->theme->display_ip_list($event->page, $this->count_upload_ips($event->user), $this->count_comment_ips($event->user));
			}
		}

		// user info is shown on all pages
		if(is_a($event, 'PageRequestEvent')) {
			global $user;
			global $page;

			if($user->is_anonymous()) {
				$this->theme->display_login_block($event->page);
			}
			else {
				$ubbe = new UserBlockBuildingEvent($user);
				send_event($ubbe);
				ksort($ubbe->parts);
				$this->theme->display_user_block($page, $user, $ubbe->parts);
			}
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("User Options");
			$sb->add_bool_option("login_signup_enabled", "Allow new signups: ");
			$sb->add_longtext_option("login_tac", "<br>Terms &amp; Conditions:<br>");
			$event->panel->add_block($sb);
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			$event->add_link("User Config", make_link("user"));
			$event->add_link("Log Out", make_link("user/logout"), 99);
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
		$addr = $_SERVER['REMOTE_ADDR'];
		$hash = md5( strtolower($name) . $pass );

		$duser = $database->get_user_by_name_and_hash($name, $hash);
		if(!is_null($duser)) {
			$user = $duser;

			setcookie(
					"shm_user", $name,
					time()+60*60*24*365, "/"
					);
			setcookie(
					"shm_session", md5($hash.$addr),
					time()+60*60*24*$config->get_int('login_memory'), "/"
					);

			$page->set_mode("redirect");
			$page->set_redirect(make_link("user"));
		}
		else {
			$this->theme->display_error($page, "Error", "No user with those details was found");
		}
	}

	private function create_user_wrapper($page) {
		global $database;
		global $config;

		if(!$config->get_bool("login_signup_enabled")) {
			$this->theme->display_signups_disabled($page);
		}
		else if(isset($_POST['name']) && isset($_POST['pass1']) && isset($_POST['pass2'])) {
			$name = trim($_POST['name']);
			$pass1 = $_POST['pass1'];
			$pass2 = $_POST['pass2'];

			if(strlen($name) < 1) {
				$this->theme->display_error($page, "Error", "Username must be at least 1 character");
			}
			else if(!preg_match('/^[a-zA-Z0-9-_ ]+$/', $name)) {
				$this->theme->display_error($page, "Error", "Username contains invalid characters. Allowed characters are letters, numbers, dash, underscore, and space");
			}
			else if($pass1 != $pass2) {
				$this->theme->display_error($page, "Error", "Passwords don't match");
			}
			else if($database->db->GetRow("SELECT * FROM users WHERE name = ?", array($name))) {
				$this->theme->display_error($page, "Error", "That username is already taken");
			}
			else {
				$addr = $_SERVER['REMOTE_ADDR'];
				$hash = md5( strtolower($name) . $pass1 );
				$email = isset($_POST['email']) ? $_POST['email'] : null;

				// FIXME: send_event()
				$database->Execute(
						"INSERT INTO users (name, pass, joindate, email) VALUES (?, ?, now(), ?)",
						array($name, $hash, $email));

				setcookie("shm_user", $name,
						time()+60*60*24*365, '/');
				setcookie("shm_session", md5($hash.$addr),
						time()+60*60*24*$config->get_int('login_memory'), '/');
				$page->set_mode("redirect");
				$page->set_redirect(make_link("user"));
			}
		}
		else {
			$this->theme->display_signup_page($page);
		}
	}
//}}} 
// Things done *to* the user {{{
	private function change_password_wrapper($page) {
		global $user;
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

			if((!$user->is_admin()) && ($name != $user->name)) {
				$page->add_block(new Block("Error",
						"You need to be an admin to change other people's passwords"));
			}
			else if($pass1 != $pass2) {
				$page->add_block(new Block("Error", "Passwords don't match"));
			}
			else {
				global $config;
				$addr = $_SERVER['REMOTE_ADDR'];
				$hash = md5( strtolower($name) . $pass1 );

				// FIXME: send_event()
				// FIXME: $duser->set_pass();
				$database->Execute(
						"UPDATE users SET pass = ? WHERE id = ?",
						array($hash, $id));

				if($id == $user->id) {
					setcookie("shm_user", $name,
							time()+60*60*24*365, '/');
					setcookie("shm_session", md5($hash.$addr),
							time()+60*60*24*$config->get_int('login_memory'), '/');
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
			$enabled = (isset($_POST['enabled']) && ($_POST['enabled'] == "on"));
			
			$duser = $database->get_user_by_id($_POST['id']);
			$duser->set_admin($admin);
			$duser->set_enabled($enabled);

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
				SELECT owner_ip, COUNT(images.id) AS count
				FROM images
				WHERE owner_id=?
				GROUP BY owner_ip
				ORDER BY MAX(posted) DESC", array($duser->id));
		return $rows;
	}
	private function count_comment_ips($duser) {
		global $database;
		$rows = $database->db->GetAssoc("
				SELECT owner_ip, COUNT(comments.id) AS count
				FROM comments
				WHERE owner_id=?
				GROUP BY owner_ip
				ORDER BY MAX(posted) DESC", array($duser->id));
		return $rows;
	}
// }}}
}
add_event_listener(new UserPage());
?>
