<?php
/*
 * Name: User Management
 * Author: Shish
 * Description: Allows people to sign up to the website
 */

class UserBlockBuildingEvent extends Event {
	var $parts = array();

	public function add_link($name, $link, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = array("name" => $name, "link" => $link);
	}
}

class UserPageBuildingEvent extends Event {
	var $display_user;
	var $stats = array();

	public function __construct(User $display_user) {
		$this->display_user = $display_user;
	}

	public function add_stats($html, $position=50) {
		while(isset($this->stats[$position])) $position++;
		$this->stats[$position] = $html;
	}
}

class UserCreationEvent extends Event {
	var $username;
	var $password;
	var $email;

	public function __construct($name, $pass, $email) {
		$this->username = $name;
		$this->password = $pass;
		$this->email = $email;
	}
}

class UserCreationException extends SCoreException {}

class UserPage extends Extension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_bool("login_signup_enabled", true);
		$config->set_default_int("login_memory", 365);
		$config->set_default_string("avatar_host", "none");
		$config->set_default_int("avatar_gravatar_size", 80);
		$config->set_default_string("avatar_gravatar_default", "");
		$config->set_default_string("avatar_gravatar_rating", "g");
		$config->set_default_bool("login_tac_bbcode", true);
	}

	public function onPageRequest(PageRequestEvent $event) {
		global $config, $database, $page, $user;

		// user info is shown on all pages
		if($user->is_anonymous()) {
			$this->theme->display_login_block($page);
		}
		else {
			$ubbe = new UserBlockBuildingEvent();
			send_event($ubbe);
			ksort($ubbe->parts);
			$this->theme->display_user_block($page, $user, $ubbe->parts);
		}

		if($event->page_matches("user_admin")) {
			if($event->get_arg(0) == "login") {
				if(isset($_POST['user']) && isset($_POST['pass'])) {
					$this->login($page);
				}
				else {
					$this->theme->display_login_page($page);
				}
			}
			else if($event->get_arg(0) == "recover") {
				$user = User::by_name($_POST['username']);
				if(is_null($user)) {
					$this->theme->display_error(404, "Error", "There's no user with that name");
				}
				if(is_null($user->email)) {
					//
				}
			}
			else if($event->get_arg(0) == "create") {
				if(!$config->get_bool("login_signup_enabled")) {
					$this->theme->display_signups_disabled($page);
				}
				else if(!isset($_POST['name'])) {
					$this->theme->display_signup_page($page);
				}
				else if($_POST['pass1'] != $_POST['pass2']) {
					$this->theme->display_error(400, "Password Mismatch", "Passwords don't match");
				}
				else {
					try {
						if(!captcha_check()) {
							throw new UserCreationException("Error in captcha");
						}

						$uce = new UserCreationEvent($_POST['name'], $_POST['pass1'], $_POST['email']);
						send_event($uce);
						$this->set_login_cookie($uce->username, $uce->password);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("user"));
					}
					catch(UserCreationException $ex) {
						$this->theme->display_error(400, "User Creation Error", $ex->getMessage());
					}
				}
			}
			else if($event->get_arg(0) == "list") {
// select users.id,name,joindate,admin,
// (select count(*) from images where images.owner_id=users.id) as images,
// (select count(*) from comments where comments.owner_id=users.id) as comments from users;

// select users.id,name,joindate,admin,image_count,comment_count
// from users
// join (select owner_id,count(*) as image_count from images group by owner_id) as _images on _images.owner_id=users.id
// join (select owner_id,count(*) as comment_count from comments group by owner_id) as _comments on _comments.owner_id=users.id;
				$this->theme->display_user_list($page, User::by_list(0), $user);
			}

			if(!$user->check_auth_token()) {
				return;
			}

			if($event->get_arg(0) == "logout") {
				set_prefixed_cookie("session", "", time()+60*60*24*$config->get_int('login_memory'), "/");
				if(CACHE_HTTP || SPEED_HAX) {
					# to keep as few versions of content as possible,
					# make cookies all-or-nothing
					set_prefixed_cookie("user", "", time()+60*60*24*$config->get_int('login_memory'), "/");
				}
				log_info("user", "Logged out");
				$page->set_mode("redirect");
				$page->set_redirect(make_link());
			}
			else if($event->get_arg(0) == "change_pass") {
				if(isset($_POST['id']) && isset($_POST['pass1']) && isset($_POST['pass2'])) {
					$duser = User::by_id($_POST['id']);
					$pass1 = $_POST['pass1'];
					$pass2 = $_POST['pass2'];
					$this->change_password_wrapper($duser, $pass1, $pass2);
				}
			}
			else if($event->get_arg(0) == "change_email") {
				if(isset($_POST['id']) && isset($_POST['address'])) {
					$duser = User::by_id($_POST['id']);
					$address = $_POST['address'];
					$this->change_email_wrapper($duser, $address);
				}
			}
			else if($event->get_arg(0) == "change_class") {
				global $_user_classes;
				if(isset($_POST['id']) && isset($_POST['class'])) {
					$duser = User::by_id($_POST['id']);
					$class = $_POST['class'];
					if(!array_key_exists($class, $_user_classes)) {
						throw Exception("Invalid user class: ".html_escape($class));
					}
					$this->change_class_wrapper($duser, $class);
				}
			}
			else if($event->get_arg(0) == "delete_user") {
				$this->delete_user($page, isset($_POST["with_images"]), isset($_POST["with_comments"]));
			}
		}

		if($event->page_matches("user")) {
			$display_user = ($event->count_args() == 0) ? $user : User::by_name($event->get_arg(0));
			if($event->count_args() == 0 && $user->is_anonymous()) {
				$this->theme->display_error(401, "Not Logged In",
					"You aren't logged in. First do that, then you can see your stats.");
			}
			else if(!is_null($display_user) && ($display_user->id != $config->get_int("anon_id"))) {
				send_event(new UserPageBuildingEvent($display_user));
			}
			else {
				$this->theme->display_error(404, "No Such User",
					"If you typed the ID by hand, try again; if you came from a link on this ".
					"site, it might be bug report time...");
			}
		}
	}

	public function onUserPageBuilding(UserPageBuildingEvent $event) {
		global $page, $user, $config;

		$h_join_date = autodate($event->display_user->join_date);
		$event->add_stats("Joined: $h_join_date", 10);

		$av = $event->display_user->get_avatar_html();
		if($av) $event->add_stats($av, 0);

		ksort($event->stats);
		$this->theme->display_user_page($event->display_user, $event->stats);
		if($user->id == $event->display_user->id) {
			$ubbe = new UserBlockBuildingEvent();
			send_event($ubbe);
			ksort($ubbe->parts);
			$this->theme->display_user_links($page, $user, $ubbe->parts);
		}
		if(
			($user->can("view_ip") || ($user->is_logged_in() && $user->id == $event->display_user->id)) && # admin or self-user
			($event->display_user->id != $config->get_int('anon_id')) # don't show anon's IP list, it is le huge
		) {
			$this->theme->display_ip_list(
				$page,
				$this->count_upload_ips($event->display_user),
				$this->count_comment_ips($event->display_user));
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		global $config;

		$hosts = array(
			"None" => "none",
			"Gravatar" => "gravatar"
		);

		$sb = new SetupBlock("User Options");
		$sb->add_bool_option("login_signup_enabled", "Allow new signups: ");
		$sb->add_longtext_option("login_tac", "<br>Terms &amp; Conditions:<br>");
		$sb->add_choice_option("avatar_host", $hosts, "<br>Avatars: ");

		if($config->get_string("avatar_host") == "gravatar") {
			$sb->add_label("<br>&nbsp;<br><b>Gravatar Options</b>");
			$sb->add_choice_option("avatar_gravatar_type",
				array(
					'Default'=>'default',
					'Wavatar'=>'wavatar',
					'Monster ID'=>'monsterid',
					'Identicon'=>'identicon'
				),
				"<br>Type: ");
			$sb->add_choice_option("avatar_gravatar_rating",
				array('G'=>'g', 'PG'=>'pg', 'R'=>'r', 'X'=>'x'),
				"<br>Rating: ");
		}

		$event->panel->add_block($sb);
	}

	public function onUserBlockBuilding(UserBlockBuildingEvent $event) {
		$event->add_link("My Profile", make_link("user"));
		$event->add_link("Log Out", make_link("user_admin/logout"), 99);
	}

	public function onUserCreation(UserCreationEvent $event) {
		$this->check_user_creation($event);
		$this->create_user($event);
	}

	public function onSearchTermParse(SearchTermParseEvent $event) {
		global $user;

		$matches = array();
		if(preg_match("/^(poster|user)=(.*)$/i", $event->term, $matches)) {
			$duser = User::by_name($matches[2]);
			if(!is_null($duser)) {
				$user_id = $duser->id;
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
		else if($user->can("view_ip") && preg_match("/^(poster|user)_ip=([0-9\.]+)$/i", $event->term, $matches)) {
			$user_ip = $matches[2]; // FIXME: ip_escape?
			$event->add_querylet(new Querylet("images.owner_ip = '$user_ip'"));
		}
	}
// }}}
// Things done *with* the user {{{
	private function login(Page $page)  {
		global $user;

		$name = $_POST['user'];
		$pass = $_POST['pass'];
		$hash = md5(strtolower($name) . $pass);

		if(empty($name) || empty($pass)) {
			$this->theme->display_error(400, "Error", "Username or password left blank");
			return;
		}

		$duser = User::by_name_and_hash($name, $hash);
		if(!is_null($duser)) {
			$user = $duser;
			$this->set_login_cookie($name, $pass);
			if($user->is_admin()) {
				log_warning("user", "Admin logged in");
			}
			else {
				log_info("user", "User logged in");
			}
			$page->set_mode("redirect");
			$page->set_redirect(make_link("user"));
		}
		else {
			log_warning("user", "Failed to log in as ".html_escape($name)." [$hash]");
			$this->theme->display_error(401, "Error", "No user with those details was found");
		}
	}

	private function check_user_creation($event) { // FIXME type
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
		else if($database->get_row("SELECT * FROM users WHERE name = :name", array("name"=>$name))) {
			throw new UserCreationException("That username is already taken");
		}
	}

	private function create_user($event) { // FIXME type
		global $database;

		$hash = md5(strtolower($event->username) . $event->password);
		$email = (!empty($event->email)) ? $event->email : null;

		// if there are currently no admins, the new user should be one
		$need_admin = ($database->get_one("SELECT COUNT(*) FROM users WHERE class='admin'") == 0);
		$class = $need_admin ? 'admin' : 'user';

		$database->Execute(
				"INSERT INTO users (name, pass, joindate, email, class) VALUES (:username, :hash, now(), :email, :class)",
				array("username"=>$event->username, "hash"=>$hash, "email"=>$email, "class"=>$class));
		$uid = $database->get_last_insert_id('users_id_seq');
		log_info("user", "Created User #$uid ({$event->username})");
	}

	private function set_login_cookie(/*string*/ $name, /*string*/ $pass) {
		global $config;

		$addr = get_session_ip($config);
		$hash = md5(strtolower($name) . $pass);

		set_prefixed_cookie("user", $name,
				time()+60*60*24*365, '/');
		set_prefixed_cookie("session", md5($hash.$addr),
				time()+60*60*24*$config->get_int('login_memory'), '/');
	}
//}}}
// Things done *to* the user {{{
	private function user_can_edit_user(User $a, User $b) {
		if($a->is_anonymous()) {
			$this->theme->display_error(401, "Error", "You aren't logged in");
		}
		if($a->name == $b->name) {
			return true;
		}
		if($b->can("protected")) {
			if($a->class->name == "admin") {
				return true;
			}
			else {
				$this->theme->display_error(401, "Error", "You need to be an admin to change other people's details");
			}
		}
		else {
			if($a->can("edit_user_info")) {
				return true;
			}
			else {
				$this->theme->display_error(401, "Error", "You need to be an admin to change other people's details");
			}
		}
		return false;
	}

	private function redirect_to_user(User $duser) {
		global $page, $user;

		if($user->id == $duser->id) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link("user"));
		}
		else {
			$page->set_mode("redirect");
			$page->set_redirect(make_link("user/{$duser->name}"));
		}
	}

	private function change_password_wrapper(User $duser, $pass1, $pass2) {
		global $user;

		if($this->user_can_edit_user($user, $duser)) {
			if($pass1 != $pass2) {
				$this->theme->display_error(400, "Error", "Passwords don't match");
			}
			else {
				// FIXME: send_event()
				$duser->set_password($pass1);

				if($id == $user->id) {
					$this->set_login_cookie($duser->name, $pass1);
				}
				$this->redirect_to_user($duser);
			}
		}
	}

	private function change_email_wrapper(User $duser, /*string(email)*/ $address) {
		global $user;

		if($this->user_can_edit_user($user, $duser)) {
			$duser->set_email($address);
			$this->redirect_to_user($duser);
		}
	}

	private function change_class_wrapper(User $duser, /*string(class)*/ $class) {
		global $user;

		if($user->class->name == "admin") {
			$duser = User::by_id($_POST['id']);
			$duser->set_class($class);
			$this->redirect_to_user($duser);
		}
	}
// }}}
// ips {{{
	private function count_upload_ips(User $duser) {
		global $database;
		$rows = $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(images.id) AS count,
					MAX(posted) AS most_recent
				FROM images
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY most_recent DESC", array("id"=>$duser->id));
		return $rows;
	}
	private function count_comment_ips(User $duser) {
		global $database;
		$rows = $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(comments.id) AS count,
					MAX(posted) AS most_recent
				FROM comments
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY most_recent DESC", array("id"=>$duser->id));
		return $rows;
	}
	
	private function delete_user(Page $page, /*boolean*/ $with_images=false, /*boolean*/ $with_comments=false) {
		global $user;
		global $config;
		global $database;
		
		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_block(new NavBlock());
		
		if (!$user->can("delete_user")) {
			$page->add_block(new Block("Not Admin", "Only admins can delete accounts"));
		}
		else if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
			$page->add_block(new Block("No ID Specified",
					"You need to specify the account number to edit"));
		}
		else {
			if($with_images) {
				$rows = $database->get_all("SELECT * FROM images WHERE owner_id = :owner_id", array("owner_id" => $_POST['id']));
				foreach ($rows as $key => $value) {
					$image = Image::by_id($value['id']);
					if($image) {
						send_event(new ImageDeletionEvent($image));
					}
				}
			}
			else {
				$database->Execute(
					"UPDATE images SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
					array("new_owner_id" => $config->get_int('anon_id'), "old_owner_id" => $_POST['id'])
				);
			}

			if($with_comments) {
				$database->execute("DELETE FROM comments WHERE owner_id = :owner_id", array("owner_id" => $_POST['id']));
			}
			else {
				$database->Execute(
					"UPDATE comments SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
					array("new_owner_id" => $config->get_int('anon_id'), "old_owner_id" => $_POST['id'])
				);
			}

			$database->execute(
				"DELETE FROM users WHERE id = :id",
				array("id" => $_POST['id'])
			);
		
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/list"));
		}
	}
// }}}
}
?>
