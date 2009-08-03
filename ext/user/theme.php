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
			$html .= "<td><a href='".make_link("user/"+$duser->name)."'>".html_escape($duser->name)."</a></td>";
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
		$html = "Logged in as $h_name";
		foreach($parts as $part) {
			$html .= "<br><a href='{$part["link"]}'>{$part["name"]}</a>";
		}
		$page->add_block(new Block("User Links", $html, "left", 90));
	}

	public function display_signup_page(Page $page) {
		global $config;
		$tac = $config->get_string("login_tac", "");

		$tfe = new TextFormattingEvent($tac);
		send_event($tfe);
		$tac = $tfe->formatted;

		if(empty($tac)) {$html = "";}
		else {$html = "<p>$tac</p>";}

		$html .= "
		<form action='".make_link("user_admin/create")."' method='POST'>
			<table style='width: 300px;'>
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

	public function display_signups_disabled(Page $page) {
		$page->set_title("Signups Disabled");
		$page->set_heading("Signups Disabled");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Signups Disabled",
			"The board admin has disabled the ability to create new accounts~"));
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
		$page->add_block(new Block("Login", $html, "left", 90));
	}

	public function display_ip_list(Page $page, $uploads, $comments) {
		$html = "<table id='ip-history'>";
		$html .= "<tr><td>Uploaded from: ";
		$n = 0;
		foreach($uploads as $ip => $count) {
			$html .= "<br>$ip ($count)";
			if(++$n >= 20) {
				$html .= "<br>...";
				break;
			}
		}

		$html .= "</td><td>Commented from:";
		$n = 0;
		foreach($comments as $ip => $count) {
			$html .= "<br>$ip ($count)";
			if(++$n >= 20) {
				$html .= "<br>...";
				break;
			}
		}

		$html .= "</td></tr>";
		$html .= "<tr><td colspan='2'>(Most recent at top)</td></tr></table>";

		$page->add_block(new Block("IPs", $html));
	}

	public function display_user_page(Page $page, User $duser, User $user) {
		$page->set_title("{$duser->name}'s Page");
		$page->set_heading("{$duser->name}'s Page");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Stats", $this->build_stats($duser)));

		if(!$user->is_anonymous()) {
			if($user->id == $duser->id || $user->is_admin()) {
				$page->add_block(new Block("Options", $this->build_options($duser), "main", 20));
			}
			if($user->is_admin()) {
				$page->add_block(new Block("More Options", $this->build_more_options($duser)));
			}
		}
	}

	protected function build_stats(User $duser) {
		global $database;
		global $config;

		$h_join_date = html_escape($duser->join_date);
		$i_image_count = Image::count_images(array("user_id={$duser->id}"));
		$i_comment_count = Comment::count_comments_by_user($duser);

		$i_days_old = ((time() - strtotime($duser->join_date)) / 86400) + 1;
		$h_image_rate = sprintf("%.1f", ($i_image_count / $i_days_old));
		$h_comment_rate = sprintf("%.1f", ($i_comment_count / $i_days_old));

		$u_id = url_escape($duser->id);
		$images_link = make_link("post/list/user_id=$u_id/1");

		return "
			Join date: $h_join_date
			<br><a href='$images_link'>Images uploaded</a>: $i_image_count, $h_image_rate per day
			<br>Comments made: $i_comment_count, $h_comment_rate per day
			";
	}

	protected function build_options(User $duser) {
		global $database;
		global $config;

		$html = "";
		$html .= "
		<form action='".make_link("user_admin/change_pass")."' method='POST'>
			<input type='hidden' name='name' value='{$duser->name}'>
			<input type='hidden' name='id' value='{$duser->id}'>
			<table style='width: 300px;'>
				<tr><th colspan='2'>Change Password</th></tr>
				<tr><td>Password</td><td><input type='password' name='pass1'></td></tr>
				<tr><td>Repeat Password</td><td><input type='password' name='pass2'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Change Password'></td></tr>
			</table>
		</form>
		";
		return $html;
	}

	protected function build_more_options(User $duser) {
		global $database;
		global $config;

		$i_user_id = int_escape($duser->id);
		$h_is_admin = $duser->is_admin() ? " checked" : "";

		$html = "
			<form action='".make_link("user_admin/set_more")."' method='POST'>
			<input type='hidden' name='id' value='$i_user_id'>
			Admin: <input name='admin' type='checkbox'$h_is_admin>
			<p><input type='submit' value='Set'>
			</form>
			";
		return $html;
	}
// }}}
}
?>
