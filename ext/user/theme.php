<?php

class UserPageTheme extends Themelet {
	public function display_login_page($page) {
					$page->set_title("Login");
					$page->set_heading("Login");
					$page->add_block(new NavBlock());
					$page->add_block(new Block("Login There",
						"There should be a login box to the left"));
	}

	public function display_signup_page($page) {
		global $config;
		$tac = $config->get_string("login_tac");

		if(empty($tac)) {$html = "";}
		else {$html = "<p>$tac</p>";}

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

		$page->set_title("Create Account");
		$page->set_heading("Create Account");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Signup", $html));
	}

	public function display_login_block($page) {
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
		$page->add_block(new Block("Login", $html, "left", 90));
	}
}
?>
