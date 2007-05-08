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

class UserPage extends Extension {
// event handling {{{
	public function receive_event($event) {
		if(is_a($event, 'PageRequestEvent') && ($event->page == "user")) {
			global $page;
			global $user;
			global $database;
			global $config;

			if($event->get_arg(0) == "login") {
				if(isset($_POST['user']) && isset($_POST['pass'])) {
					$this->login();
				}
				else {
					$page->set_title("Login");
					$page->set_heading("Login");
					$page->add_side_block(new NavBlock());
					$page->add_main_block(new Block("Login There",
						"There should be a login box to the left"));
				}
			}
			else if($event->get_arg(0) == "logout") {
				setcookie("shm_session", "", time()+60*60*24*$config->get_int('login_memory'), "/");
				$page->set_mode("redirect");
				$page->set_redirect(make_link("index"));
			}
			else if($event->get_arg(0) == "changepass") {
				$this->change_password_wrapper();
			}
			else if($event->get_arg(0) == "create") {
				$this->create_user_wrapper();
			}
			else if($event->get_arg(0) == "set_more") {
				$this->set_more_wrapper();
			}
			else { // view
				$duser = ($event->count_args() == 0) ? $user : $database->get_user_by_name($event->get_arg(0));
				$this->build_user_page($duser);
			}
		}

		// user info is shown on all pages
		if(is_a($event, 'PageRequestEvent')) {
			global $user;
			global $page;

			if($user->is_anonymous()) {
				$page->add_side_block(new Block("Login", $this->build_login_block()), 90);
			}
			else {
				$page->add_side_block(new Block("User Links", $this->build_links_block()), 90);
			}
		}
		
		if(is_a($event, 'SetupBuildingEvent')) {
			$sb = new SetupBlock("User Options");
			$sb->add_int_option("login_memory", "Login memory: "); $sb->add_label(" days");
			$sb->add_bool_option("login_signup_enabled", "<br>Allow new signups: ");
			$sb->add_longtext_option("login_tac", "<br>Terms &amp; Conditions:<br>");
			$event->panel->add_main_block($sb);
		}
		if(is_a($event, 'ConfigSaveEvent')) {
			$event->config->set_int_from_post("login_memory");
			$event->config->set_bool_from_post("login_signup_enabled");
			$event->config->set_string_from_post("login_tac");
		}

		if(is_a($event, 'UserBlockBuildingEvent')) {
			$event->add_link("User Config", make_link("user"));
			$event->add_link("Log Out", make_link("user/logout"));
		}
	}
// }}}
// Things done *with* the user {{{
	private function login()  {
		global $page;
		global $database;
		global $config;
		global $user;

		$name = $_POST['user'];
		$pass = $_POST['pass'];
		$addr = $_SERVER['REMOTE_ADDR'];
		$hash = md5( strtolower($name) . $pass );

		$duser = $database->get_user($name, $hash);
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
			$page->set_title("Permission Denied");
			$page->set_heading("Permission Denied");
			$page->add_side_block(new NavBlock(), 0);
			$page->add_main_block(new Block("Error", "No user with those details was found"));
		}
	}

	private function create_user_wrapper() {
		global $page;
		global $database;
		global $config;

		if(!$config->get_bool("login_signup_enabled")) {
			$page->set_title("Signups Disabled");
			$page->set_heading("Signups Disabled");
			$page->add_side_block(new NavBlock());
			$page->add_main_block(new Block("Signups Disabled",
				"The board admin has disabled the ability to create new accounts~"));
		}
		else if(isset($_POST['name']) && isset($_POST['pass1']) && isset($_POST['pass2'])) {
			$name = trim($_POST['name']);
			$pass1 = $_POST['pass1'];
			$pass2 = $_POST['pass2'];

			$page->set_title("Error");
			$page->set_heading("Error");
			$page->add_side_block(new NavBlock());
			if(strlen($name) < 1) {
				$page->add_main_block(new Block("Error", "Username must be at least 1 character"));
			}
			else if($pass1 != $pass2) {
				$page->add_main_block(new Block("Error", "Passwords don't match"));
			}
			else if($database->db->GetRow("SELECT * FROM users WHERE name = ?", array($name))) {
				$page->add_main_block(new Block("Error", "That username is already taken"));
			}
			else {
				$addr = $_SERVER['REMOTE_ADDR'];
				$hash = md5( strtolower($name) . $pass1 );
				$email = isset($_POST['email']) ? $_POST['email'] : null;

				// FIXME: send_event()
				$database->db->Execute(
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
			$page->set_title("Create Account");
			$page->set_heading("Create Account");
			$page->add_side_block(new NavBlock());
			$page->add_main_block(new Block("Signup", $this->build_signup_form()));
		}
	}
//}}} 
// Things do ne *to* the user {{{
	private function change_password_wrapper() {
		global $user;
		global $page;
		global $database;
		
		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_side_block(new NavBlock());
		if($user->is_anonymous()) {
			$page->add_main_block(new Block("Error", "You aren't logged in"));
		}
		else if(isset($_POST['id']) && isset($_POST['name']) &&
				isset($_POST['pass1']) && isset($_POST['pass2'])) {
			$name = $_POST['name'];
			$id = $_POST['id'];
			$pass1 = $_POST['pass1'];
			$pass2 = $_POST['pass2'];

			if((!$user->is_admin()) && ($name != $user->name)) {
				$page->add_main_block(new Block("Error",
						"You need to be an admin to change other people's passwords"));
			}
			else if($pass1 != $pass2) {
				$page->add_main_block(new Block("Error", "Passwords don't match"));
			}
			else {
				global $config;
				$addr = $_SERVER['REMOTE_ADDR'];
				$hash = md5( strtolower($name) . $pass1 );

				// FIXME: send_event()
				// FIXME: $duser->set_pass();
				$database->db->Execute(
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

	private function set_more_wrapper() {
		global $page;
		global $user;
		global $database;
		
		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_side_block(new NavBlock());
		if(!$user->is_admin()) {
			$page->add_main_block(new Block("Not Admin", "Only admins can edit accounts"));
		}
		else if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
			$page->add_main_block(new Block("No ID Specified",
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
// HTML building {{{
	private function build_signup_form() {
		global $config;
		$tac = $config->get_string("login_tac");

		if(empty($tac)) {
			$html = "";
		}
		else {
			$html = "<p>$tac</p>";
		}
		$html .= "
		<form action='".make_link("user/create")."' method='POST'>
			<table style='width: 300px;' border='1'>
				<tr><td>Name</td><td><input type='text' name='name'></td></tr>
				<tr><td>Password</td><td><input type='password' name='pass1'></td></tr>
				<tr><td>Repeat Password</td><td><input type='password' name='pass2'></td></tr>
				<tr><td>Email (Optional)</td><td><input type='text' name='email'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Create Account'></td></tr>
			</table>
		</form>
		";
		return $html;
	}

	private function build_user_page($duser) {
		global $page;
		global $user;
		if(!is_null($duser)) {
			$page->set_title("{$duser->name}'s Page");
			$page->set_heading("{$duser->name}'s Page");
			$page->add_side_block(new NavBlock(), 0);
			$page->add_main_block(new Block("Stats", $this->build_stats($duser)));

			if(!$user->is_anonymous()) {
				if($user->id == $duser->id || $user->is_admin()) {
					$page->add_main_block(new Block("Options", $this->build_options($duser)));
				}
				if($user->is_admin()) {
					$page->add_main_block(new Block("More Options", $this->build_more_options($duser)));
				}
			}
		}
		else {
			$page->set_title("No Such User");
			$page->set_heading("No Such User");
			$page->add_side_block(new NavBlock(), 0);
			$page->add_main_block(new Block("No User By That ID",
						"If you typed the ID by hand, try again; if you came from a link on this ".
						"site, it might be bug report time..."));
		}
	}

	private function build_stats($duser) {
		global $database;
		global $config;

		$i_days_old = int_escape($duser->get_days_old());
		$h_join_date = html_escape($duser->join_date);
		$i_image_count = int_escape($duser->get_image_count());
		$i_comment_count = int_escape($duser->get_comment_count());

		$i_days_old2 = ($i_days_old == 0) ? 1 : $i_days_old;

		$h_image_rate = sprintf("%3.1f", ($i_image_count / $i_days_old2));
		$h_comment_rate = sprintf("%3.1f", ($i_comment_count / $i_days_old2));

		return "
			Join date: $h_join_date ($i_days_old days old)
			<br>Images uploaded: $i_image_count ($h_image_rate / day)
			<br>Comments made: $i_comment_count ($h_comment_rate / day)
			";
	}

	private function build_options($duser) {
		global $database;
		global $config;

		$html = "";
		$html .= "
		<form action='".make_link("user/changepass")."' method='POST'>
			<input type='hidden' name='name' value='{$duser->name}'>
			<input type='hidden' name='id' value='{$duser->id}'>
			<table style='width: 300px;' border='1'>
				<tr><td colspan='2'>Change Password</td></tr>
				<tr><td>Password</td><td><input type='password' name='pass1'></td></tr>
				<tr><td>Repeat Password</td><td><input type='password' name='pass2'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Change Password'></td></tr>
			</table>
		</form>
		";
		return $html;
	}

	private function build_more_options($duser) {
		global $database;
		global $config;

		$i_user_id = int_escape($duser->id);
		$h_is_admin = $duser->is_admin() ? " checked" : "";
		$h_is_enabled = $duser->is_enabled() ? " checked" : "";

		$html = "
			<form action='".make_link("user/set_more")."' method='POST'>
			<input type='hidden' name='id' value='$i_user_id'>
			Admin: <input name='admin' type='checkbox'$h_is_admin>
			<br>Enabled: <input name='enabled' type='checkbox'$h_is_enabled>
			<p><input type='submit' value='Set'>
			</form>
			";
		return $html;
	}

	private function build_links_block() {
		global $user;

		$ubbe = new UserBlockBuildingEvent($user);

		send_event($ubbe);

		$h_name = html_escape($user->name);
		$html = "Logged in as $h_name<br>";

		$html .= join("\n<br/>", $ubbe->parts);
		
		return $html;
	}

	private function build_login_block() {
		global $config;
		$html = "
			<form action='".make_link("user/login")."' method='POST'>
			<table border='1' summary='Login Form'>
			<tr><td width='70'>Name</td><td width='70'><input type='text' name='user'></td></tr>
			<tr><td>Password</td><td><input type='password' name='pass'></td></tr>
			<tr><td colspan='2'><input type='submit' name='gobu' value='Log In'></td></tr>
			</table>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("user/create")."'>Create Account</a></small>";
		}
		return $html;
	}
// }}}
}
add_event_listener(new UserPage());
?>
