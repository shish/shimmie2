<?php

class UserPageTheme extends Themelet
{
    public function display_login_page(Page $page)
    {
        $page->set_title("Login");
        $page->set_heading("Login");
        $page->add_block(new NavBlock());
        $page->add_block(new Block(
            "Login There",
            "There should be a login box to the left"
        ));
    }

    /**
     * #param User[] $users
     */
    public function display_user_list(Page $page, array $users, User $user, int $page_num, int $page_total)
    {
        $page->set_title("User List");
        $page->set_heading("User List");
        $page->add_block(new NavBlock());

        $html = "<table class='zebra'>";

        $html .= "<tr>";
        $html .= "<td>Name</td>";
        if ($user->can(Permissions::DELETE_USER)) {
            $html .= "<td>Email</td>";
        }
        $html .= "<td>Class</td>";
        $html .= "<td>Action</td>";
        $html .= "</tr>";

        $h_username = html_escape(@$_GET['username']);
        $h_email = html_escape(@$_GET['email']);
        $h_class = html_escape(@$_GET['class']);

        $html .= "<tr>" . make_form("user_admin/list", "GET");
        $html .= "<td><input type='text' name='username' value='$h_username'/></td>";
        if ($user->can(Permissions::DELETE_USER)) {
            $html .= "<td><input type='text' name='email' value='$h_email'/></td>";
        }
        $html .= "<td><input type='text' name='class' value='$h_class'/></td>";
        $html .= "<td><input type='submit' value='Search'/></td>";
        $html .= "</form></tr>";

        foreach ($users as $duser) {
            $h_name = html_escape($duser->name);
            $h_email = html_escape($duser->email);
            $h_class = html_escape($duser->class->name);
            $u_link = make_link("user/" . url_escape($duser->name));
            $u_posts = make_link("post/list/user_id=" . url_escape($duser->id) . "/1");

            $html .= "<tr>";
            $html .= "<td><a href='$u_link'>$h_name</a></td>";
            if ($user->can(Permissions::DELETE_USER)) {
                $html .= "<td>$h_email</td>";
            }
            $html .= "<td>$h_class</td>";
            $html .= "<td><a href='$u_posts'>Show Posts</a></td>";
            $html .= "</tr>";
        }

        $html .= "</table>";

        $page->add_block(new Block("Users", $html));
        $this->display_paginator($page, "user_admin/list", $this->get_args(), $page_num, $page_total);
    }

    protected function ueie($var)
    {
        if (isset($_GET[$var])) {
            return $var."=".url_escape($_GET[$var]);
        } else {
            return "";
        }
    }
    protected function get_args()
    {
        $args = "";
        // Check if each arg is actually empty and skip it if so
        if (strlen($this->ueie("username"))) {
            $args .= $this->ueie("username")."&";
        }
        if (strlen($this->ueie("email"))) {
            $args .= $this->ueie("email")."&";
        }
        if (strlen($this->ueie("class"))) {
            $args .= $this->ueie("class")."&";
        }
        // If there are no args at all, set $args to null to prevent an unnecessary ? at the end of the paginator url
        if (strlen($args) == 0) {
            $args = null;
        }
        return $args;
    }

    public function display_user_links(Page $page, User $user, $parts)
    {
        # $page->add_block(new Block("User Links", join(", ", $parts), "main", 10));
    }

    public function display_user_block(Page $page, User $user, $parts)
    {
        $h_name = html_escape($user->name);
        $html = 'Logged in as '.$h_name;
        foreach ($parts as $part) {
            $html .= '<br><a href="'.$part["link"].'">'.$part["name"].'</a>';
        }
        $page->add_block(new Block("User Links", $html, "left", 90));
    }

    public function display_signup_page(Page $page)
    {
        global $config;
        $tac = $config->get_string("login_tac", "");

        if ($config->get_bool("login_tac_bbcode")) {
            $tfe = new TextFormattingEvent($tac);
            send_event($tfe);
            $tac = $tfe->formatted;
        }

        if (empty($tac)) {
            $html = "";
        } else {
            $html = '<p>'.$tac.'</p>';
        }

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

    public function display_signups_disabled(Page $page)
    {
        $page->set_title("Signups Disabled");
        $page->set_heading("Signups Disabled");
        $page->add_block(new NavBlock());
        $page->add_block(new Block(
            "Signups Disabled",
            "The board admin has disabled the ability to create new accounts~"
        ));
    }

    public function display_login_block(Page $page)
    {
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
        if ($config->get_bool("login_signup_enabled")) {
            $html .= "<small><a href='".make_link("user_admin/create")."'>Create Account</a></small>";
        }
        $page->add_block(new Block("Login", $html, "left", 90));
    }

    public function display_ip_list(Page $page, array $uploads, array $comments, array $events)
    {
        $html = "<table id='ip-history'>";
        $html .= "<tr><td>Uploaded from: ";
        $n = 0;
        foreach ($uploads as $ip => $count) {
            $html .= '<br>'.$ip.' ('.$count.')';
            if (++$n >= 20) {
                $html .= "<br>...";
                break;
            }
        }

        $html .= "</td><td>Commented from:";
        $n = 0;
        foreach ($comments as $ip => $count) {
            $html .= '<br>'.$ip.' ('.$count.')';
            if (++$n >= 20) {
                $html .= "<br>...";
                break;
            }
        }

        $html .= "</td><td>Logged Events:";
        $n = 0;
        foreach ($events as $ip => $count) {
            $html .= '<br>'.$ip.' ('.$count.')';
            if (++$n >= 20) {
                $html .= "<br>...";
                break;
            }
        }

        $html .= "</td></tr>";
        $html .= "<tr><td colspan='3'>(Most recent at top)</td></tr></table>";

        $page->add_block(new Block("IPs", $html, "main", 70));
    }

    public function display_user_page(User $duser, $stats)
    {
        global $page;
        assert(is_array($stats));
        $stats[] = 'User ID: '.$duser->id;

        $page->set_title(html_escape($duser->name)."'s Page");
        $page->set_heading(html_escape($duser->name)."'s Page");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Stats", join("<br>", $stats), "main", 10));
    }

    public function build_options(User $duser, UserOptionsBuildingEvent $event)
    {
        global $config, $user;
        $html = "";
        if ($duser->id != $config->get_int('anon_id')) {  //justa fool-admin protection so they dont mess around with anon users.

            if ($user->can(Permissions::EDIT_USER_NAME)) {
                $html .= "
				<p>".make_form(make_link("user_admin/change_name"))."
					<input type='hidden' name='id' value='{$duser->id}'>
					<table class='form'>
						<thead><tr><th colspan='2'>Change Name</th></tr></thead>
						<tbody><tr><th>New name</th><td><input type='text' name='name' value='".html_escape($duser->name)."'></td></tr></tbody>
						<tfoot><tr><td colspan='2'><input type='Submit' value='Set'></td></tr></tfoot>
					</table>
				</form>
				</p>";
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
            </p>
			<p>".make_form(make_link("user_admin/change_email"))."
				<input type='hidden' name='id' value='{$duser->id}'>
				<table class='form'>
					<thead><tr><th colspan='2'>Change Email</th></tr></thead>
					<tbody><tr><th>Address</th><td><input type='text' name='address' value='".html_escape($duser->email)."'></td></tr></tbody>
					<tfoot><tr><td colspan='2'><input type='Submit' value='Set'></td></tr></tfoot>
				</table>
			</form>
			</p>";

            $i_user_id = int_escape($duser->id);

            if ($user->can(Permissions::EDIT_USER_CLASS)) {
                global $_shm_user_classes;
                $class_html = "";
                foreach ($_shm_user_classes as $name => $values) {
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
				</p>";
            }

            if ($user->can(Permissions::DELETE_USER)) {
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
				</p>";
            }
            foreach ($event->parts as $part) {
                $html .= $part;
            }
        }
        return $html;
    }

    public function get_help_html()
    {
        global $user;
        $output = '<p>Search for images posted by particular individuals.</p>
        <div class="command_example">
        <pre>poster=username</pre>
        <p>Returns images posted by "username".</p>
        </div> 
        <div class="command_example">
        <pre>poster_id=123</pre>
        <p>Returns images posted by user 123.</p>
        </div> 
        ';


        if ($user->can(Permissions::VIEW_IP)) {
            $output .="
        <div class=\"command_example\">
                <pre>poster_ip=127.0.0.1</pre>
                <p>Returns images posted from IP 127.0.0.1.</p>
                </div> 
                ";
        }
        return $output;
    }
}
