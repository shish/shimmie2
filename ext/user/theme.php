<?php
use function \MicroHTML\{emptyHTML,rawHTML,TABLE,TBODY,TFOOT,TR,TH,TD,LABEL,INPUT,SMALL,A,BR,P};

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

    public function display_user_list(Page $page, $table, $paginator)
    {
        $page->set_title("User List");
        $page->set_heading("User List");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Users", $table . $paginator));
    }

    public function display_user_links(Page $page, User $user, $parts)
    {
        # $page->add_block(new Block("User Links", join(", ", $parts), "main", 10));
    }

    public function display_user_block(Page $page, User $user, $parts)
    {
        $html = emptyHTML('Logged in as ', $user->name);
        foreach ($parts as $part) {
            $html->appendChild(BR());
            $html->appendChild(A(["href"=>$part["link"]], $part["name"]));
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

        $form = SHM_FORM(make_link("user_admin/create"));
        $form->appendChild(
            TABLE(
                ["class"=>"form"],
                TBODY(
                    TR(
                        TH("Name"),
                        TD(INPUT(["type"=>'text', "name"=>'name', "required"=>true]))
                    ),
                    TR(
                        TH("Password"),
                        TD(INPUT(["type"=>'password', "name"=>'pass1', "required"=>true]))
                    ),
                    TR(
                        TH(rawHTML("Repeat&nbsp;Password")),
                        TD(INPUT(["type"=>'password', "name"=>'pass2', "required"=>true]))
                    ),
                    TR(
                        TH(rawHTML("Email&nbsp;(Optional)")),
                        TD(INPUT(["type"=>'email', "name"=>'email']))
                    ),
                    TR(
                        TD(["colspan"=>"2"], rawHTML(captcha_get_html()))
                    ),
                ),
                TFOOT(
					TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>"Create Account"])))
                )
            )
        );

        $html = emptyHTML(
            $tac ? P($tac) : null,
            $form
        );

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
        global $config, $user;
        $form = SHM_FORM(make_link("user_admin/login"));
        $form->appendChild(
            TABLE(
                ["style"=>"width: 100%", "class"=>"form"],
                TBODY(
                    TR(
                        TH(LABEL(["for"=>"user"], "Name")),
                        TD(INPUT(["id"=>"user", "type"=>"text", "name"=>"user", "autocomplete"=>"username"]))
                    ),
                    TR(
                        TH(LABEL(["for"=>"pass"], "Password")),
                        TD(INPUT(["id"=>"pass", "type"=>"password", "name"=>"pass", "autocomplete"=>"current-password"]))
                    )
                ),
                TFOOT(
                    TR(TD(["colspan"=>"2"], INPUT(["type"=>"submit", "value"=>"Log In"])))
                )
            )
        );

        $html = emptyHTML();
        $html->appendChild($form);
        if ($config->get_bool("login_signup_enabled") && $user->can(Permissions::CREATE_USER)) {
            $html->appendChild(SMALL(A(["href"=>make_link("user_admin/create")], "Create Account")));
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
						<tr><th>Password</th><td><input type='password' name='pass1' autocomplete='new-password'></td></tr>
						<tr><th>Repeat Password</th><td><input type='password' name='pass2' autocomplete='new-password'></td></tr>
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
					<tbody><tr><th>Address</th><td><input type='text' name='address' value='".html_escape($duser->email)."' autocomplete='email' inputmode='email'></td></tr></tbody>
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
