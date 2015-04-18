<?php

class CustomUserPageTheme extends UserPageTheme {
	public function display_login_page(Page $page) {
		global $config;
		$page->set_title("Login");
		$page->set_heading("Login");
		$page->disable_left();
		$html = "
			".make_form("user_admin/login")."
				<table summary='Login Form'>
					<tr>
						<td width='70'><label for='user'>Name</label></td>
						<td width='70'><input id='user' type='text' name='user'></td>
					</tr>
					<tr>
						<td><label for='pass'>Password</label></td>
						<td><input id='pass' type='password' name='pass'></td>
					</tr>
					<tr><td colspan='2'><input type='submit' value='Log In'></td></tr>
				</table>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
		}
		$page->add_block(new Block("Login", $html, "main", 90));
	}

	public function display_user_links(Page $page, User $user, $parts) {
		// no block in this theme
	}
	public function display_login_block(Page $page) {
		// no block in this theme
	}

	public function display_user_block(Page $page, User $user, $parts) {
		$html = "";
		$blocked = array("Pools", "Pool Changes", "Alias Editor", "My Profile");
		foreach($parts as $part) {
			if(in_array($part["name"], $blocked)) continue;
			$html .= "<li><a href='{$part["link"]}'>{$part["name"]}</a>";
		}
		$page->add_block(new Block("User Links", $html, "user", 90));
	}

	public function display_signup_page(Page $page) {
		global $config;
		$tac = $config->get_string("login_tac", "");

		$tfe = new TextFormattingEvent($tac);
		send_event($tfe);
		$tac = $tfe->formatted;
		
		$reca = "<tr><td colspan='2'>".captcha_get_html()."</td></tr>";

		if(empty($tac)) {$html = "";}
		else {$html = "<p>$tac</p>";}

		$html .= "
		".make_form("user_admin/create")."
			<table style='width: 300px;'>
				<tr><td>Name</td><td><input type='text' name='name'></td></tr>
				<tr><td>Password</td><td><input type='password' name='pass1'></td></tr>
				<tr><td>Repeat Password</td><td><input type='password' name='pass2'></td></tr>
				<tr><td>Email (Optional)</td><td><input type='text' name='email'></td></tr>
				$reca;
				<tr><td colspan='2'><input type='Submit' value='Create Account'></td></tr>
			</table>
		</form>
		";

		$page->set_title("Create Account");
		$page->set_heading("Create Account");
		$page->disable_left();
		$page->add_block(new Block("Signup", $html));
	}

	/**
	 * @param Page $page
	 * @param array $uploads
	 * @param array $comments
	 */
	public function display_ip_list(Page $page, $uploads, $comments) {
		$html = "<table id='ip-history' style='width: 400px;'>";
		$html .= "<tr><td>Uploaded from: ";
		foreach($uploads as $ip => $count) {
			$html .= "<br>$ip ($count)";
		}
		$html .= "</td><td>Commented from:";
		foreach($comments as $ip => $count) {
			$html .= "<br>$ip ($count)";
		}
		$html .= "</td></tr>";
		$html .= "<tr><td colspan='2'>(Most recent at top)</td></tr></table>";

		$page->add_block(new Block("IPs", $html));
	}

	public function display_user_page(User $duser, $stats) {
		global $page;
		$page->disable_left();
		parent::display_user_page($duser, $stats);
	}
}

