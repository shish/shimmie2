<?php

class CustomUserPageTheme extends UserPageTheme {
	public function display_user_block(Page $page, User $user, $parts) {
		$h_name = html_escape($user->name);
		$html = " | ";
		foreach($parts as $part) {
			$html .= "<a href='{$part["link"]}'>{$part["name"]}</a> | ";
		}
		$page->add_block(new Block("Logged in as $h_name", $html, "head", 90));
	}

	public function display_login_block(Page $page) {
		global $config;
		$html = "
			<form action='".make_link("user_admin/login")."' method='POST'>
			<table summary='Login Form' align='center'>
			<tr><td width='70'>Name</td><td width='70'><input type='text' name='user'></td></tr>
			<tr><td>Password</td><td><input type='password' name='pass'></td></tr>
			<tr><td colspan='2'><input type='submit' name='gobu' value='Log In'></td></tr>
			</table>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
		}
		$page->add_block(new Block("Login", $html, "head", 90));
	}
}

