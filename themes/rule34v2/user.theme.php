<?php

class CustomUserPageTheme extends UserPageTheme
{
    public function display_user_block(Page $page, User $user, $parts)
    {
        $h_name = html_escape($user->name);
        $lines = [];
        foreach ($parts as $part) {
            $lines[] = "<a href='{$part["link"]}'>{$part["name"]}</a>";
        }
        if (count($lines) < 6) {
            $html = implode("\n<br>", $lines);
        } else {
            $html = implode(" | \n", $lines);
        }
        $page->add_block(new Block("Logged in as $h_name", $html, "head", 90, "UserBlockhead"));
        $page->add_block(new Block("Logged in as $h_name", $html, "left", 15, "UserBlockleft"));
    }

    public function display_login_block(Page $page)
    {
        global $config;
        $html = "
			<form action='".make_link("user_admin/login")."' method='POST'>
				<table class='form' style='width: 100%;'>
					<tr><th>Name</th><td><input type='text' name='user'></td></tr>
					<tr><th>Password</th><td><input type='password' name='pass'></td></tr>
					<tr><td colspan='2'><input type='submit' name='gobu' value='Log In'></td></tr>
				</table>
			</form>
		";
        if ($config->get_bool("login_signup_enabled")) {
            $html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
        }
        $page->add_block(new Block("Login", $html, "head", 90));
        $page->add_block(new Block("Login", $html, "left", 15));
    }
}
