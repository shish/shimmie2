<?php

class UserPageTheme extends Themelet {
	public function display_login_page(Page $page) {
		$page->set_title("Login");
		$page->set_heading("Login");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Login There",
			"There should be a login box to the left"));
	}

	public function display_user_list(Page $page, $users, User $user) {
		$page->set_title("User List");
		$page->set_heading("User List");
		$page->add_block(new NavBlock());
		$html = "<table>";
		$html .= "<tr><td>Name</td></tr>";
		foreach($users as $duser) {
			$html .= "<tr>";
			$html .= "<td><a href='".make_link("user/".url_escape($duser->name))."'>".html_escape($duser->name)."</a></td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
		$page->add_block(new Block("Users", $html));
	}

	public function display_user_links(Page $page, User $user, $parts) {
		# $page->add_block(new Block("User Links", join(", ", $parts), "main", 10));
	}

	public function display_user_block(Page $page, User $user, $parts) {
		$h_name = html_escape($user->name);
		$html = 'Logged in as '.$h_name;
		foreach($parts as $part) {
			$html .= '<br><a href="'.$part["link"].'">'.$part["name"].'</a>';
		}
		$page->add_block(new Block("User Links", $html, "left", 90));
	}

	public function display_signup_page(Page $page) {
		global $config;
		$tac = $config->get_string("login_tac", "");

		if($config->get_bool("login_tac_bbcode")) {
			$tfe = new TextFormattingEvent($tac);
			send_event($tfe);
			$tac = $tfe->formatted;
		}

		if(empty($tac)) {$html = "";}
		else {$html = '<p>'.$tac.'</p>';}

		$h_reca = "<tr><td colspan='2'>".captcha_get_html()."</td></tr>";

		$html .= '
		'.make_form(make_link("user_admin/create"))."
			<table class='form'>
				<tbody>
					<tr><th>Name</th><td><input type='text' name='name' required></td></tr>
					<tr><th>Password</th><td><input type='password' name='pass1' required></td></tr>
					<tr><th>Repeat&nbsp;Password</th><td><input type='password' name='pass2' required></td></tr>
					<tr><th>Email&nbsp;(Optional)</th><td><input type='email' name='email'></td></tr>
					$h_reca
				</tbody>
				<tfoot>
					<tr><td colspan='2'><input type='Submit' value='Create Account'></td></tr>
				</tfoot>
			</table>
		</form>
		";

		$page->set_title("Create Account");
		$page->set_heading("Create Account");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Signup", $html));
	}

	public function display_signups_disabled(Page $page) {
		$page->set_title("Signups Disabled");
		$page->set_heading("Signups Disabled");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Signups Disabled",
			"The board admin has disabled the ability to create new accounts~"));
	}

	public function display_login_block(Page $page) {
		global $config;
		$html = '
			'.make_form(make_link("user_admin/login"))."
				<table style='width: 100%;' class='form'>
					<tbody>
						<tr>
							<th><label for='user'>Name</label></th>
							<td><input id='user' type='text' name='user'></td>
						</tr>
						<tr>
							<th><label for='pass'>Password</label></th>
							<td><input id='pass' type='password' name='pass'></td>
						</tr>
					</tbody>
					<tfoot>
						<tr><td colspan='2'><input type='submit' value='Log In'></td></tr>
					</tfoot>
				</table>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
		}
		$page->add_block(new Block("Login", $html, "left", 90));
	}

	/**
	 * @param Page $page
	 * @param array $uploads
	 * @param array $comments
	 */
	public function display_ip_list(Page $page, $uploads, $comments) {
		$html = "<table id='ip-history'>";
		$html .= "<tr><td>Uploaded from: ";
		$n = 0;
		foreach($uploads as $ip => $count) {
			$html .= '<br>'.$ip.' ('.$count.')';
			if(++$n >= 20) {
				$html .= "<br>...";
				break;
			}
		}

		$html .= "</td><td>Commented from:";
		$n = 0;
		foreach($comments as $ip => $count) {
			$html .= '<br>'.$ip.' ('.$count.')';
			if(++$n >= 20) {
				$html .= "<br>...";
				break;
			}
		}

		$html .= "</td></tr>";
		$html .= "<tr><td colspan='2'>(Most recent at top)</td></tr></table>";

		$page->add_block(new Block("IPs", $html, "main", 70));
	}

	public function display_user_page(User $duser, $stats) {
		global $page, $user;
		assert(is_array($stats));
		$stats[] = 'User ID: '.$duser->id;

		$page->set_title(html_escape($duser->name)."'s Page");
		$page->set_heading(html_escape($duser->name)."'s Page");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Stats", join("<br>", $stats), "main", 10));

		if(!$user->is_anonymous()) {
			if($user->id == $duser->id || $user->can("edit_user_info")) {
				$page->add_block(new Block("Options", $this->build_options($duser), "main", 60));
			}
		}
	}

	protected function build_options(User $duser) {
		global $config, $user;
		$html = "";
		if($duser->id != $config->get_int('anon_id')){  //justa fool-admin protection so they dont mess around with anon users.
		
			if($user->can('edit_user_name')) {
				$html .= "
				<p>".make_form(make_link("user_admin/change_name"))."
					<input type='hidden' name='id' value='{$duser->id}'>
					<table class='form'>
						<thead><tr><th colspan='2'>Change Name</th></tr></thead>
						<tbody><tr><th>New name</th><td><input type='text' name='name' value='".html_escape($duser->name)."'></td></tr></tbody>
						<tfoot><tr><td colspan='2'><input type='Submit' value='Set'></td></tr></tfoot>
					</table>
				</form>
				";
			}

			$html .= "
			<p>".make_form(make_link("user_admin/change_pass"))."
				<input type='hidden' name='id' value='{$duser->id}'>
				<table class='form'>
					<thead>
						<tr><th colspan='2'>Change Password</th></tr>
					</thead>
					<tbody>
						<tr><th>Password</th><td><input type='password' name='pass1'></td></tr>
						<tr><th>Repeat Password</th><td><input type='password' name='pass2'></td></tr>
					</tbody>
					<tfoot>
						<tr><td colspan='2'><input type='Submit' value='Change Password'></td></tr>
					</tfoot>
				</table>
			</form>

			<p>".make_form(make_link("user_admin/change_email"))."
				<input type='hidden' name='id' value='{$duser->id}'>
				<table class='form'>
					<thead><tr><th colspan='2'>Change Email</th></tr></thead>
					<tbody><tr><th>Address</th><td><input type='text' name='address' value='".html_escape($duser->email)."'></td></tr></tbody>
					<tfoot><tr><td colspan='2'><input type='Submit' value='Set'></td></tr></tfoot>
				</table>
			</form>
			";

			$i_user_id = int_escape($duser->id);

			if($user->can("edit_user_class")) {
				global $_shm_user_classes;
				$class_html = "";
				foreach($_shm_user_classes as $name => $values) {
					$h_name = html_escape($name);
					$h_title = html_escape(ucwords($name));
					$h_selected = ($name == $duser->class->name ? " selected" : "");
					$class_html .= "<option value='$h_name'$h_selected>$h_title</option>\n";
				}
				$html .= "
					<p>".make_form(make_link("user_admin/change_class"))."
						<input type='hidden' name='id' value='$i_user_id'>
						<table style='width: 300px;'>
							<thead><tr><th colspan='2'>Change Class</th></tr></thead>
							<tbody><tr><td><select name='class'>$class_html</select></td></tr></tbody>
							<tfoot><tr><td><input type='submit' value='Set'></td></tr></tfoot>
						</table>
					</form>
				";
			}

			if($user->can("delete_user")) {
				$html .= "
					<p>".make_form(make_link("user_admin/delete_user"))."
						<input type='hidden' name='id' value='$i_user_id'>
						<table style='width: 300px;'>
							<thead>
								<tr><th colspan='2'>Delete User</th></tr>
							</thead>
							<tbody>
								<tr><td><input type='checkbox' name='with_images'> Delete images</td></tr>
								<tr><td><input type='checkbox' name='with_comments'> Delete comments</td></tr>
							</tbody>
							<tfoot>
								<tr><td><input type='button' class='shm-unlocker' data-unlock-sel='.deluser' value='Unlock'></td></tr>
								<tr><td><input type='submit' class='deluser' value='Delete User' disabled='true'/></td></tr>
							</tfoot>
						</table>
					</form>
				";
			}
		}
		return $html;
	}
// }}}
}

